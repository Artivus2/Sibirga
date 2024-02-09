<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "sensor".
 *
 * @property int $id
 * @property string $title
 * @property int $sensor_type_id
 * @property int $asmtp_id
 * @property int $object_id
 *
 * @property ConjunctionParameterSensor[] $conjunctionParameterSensors
 * @property MessageSensor[] $messageSensors
 * @property NominalConjunctionParameter[] $nominalConjunctionParameters
 * @property NominalEnergyMineParameter[] $nominalEnergyMineParameters
 * @property NominalEquipmentParameter[] $nominalEquipmentParameters
 * @property NominalFaceParameter[] $nominalFaceParameters
 * @property NominalOperationParameter[] $nominalOperationParameters
 * @property NominalOperationRegulationFactParameter[] $nominalOperationRegulationFactParameters
 * @property NominalOperationRegulationParameter[] $nominalOperationRegulationParameters
 * @property NominalPlaceParameter[] $nominalPlaceParameters
 * @property NominalPlastParameter[] $nominalPlastParameters
 * @property NominalPpsMineParameter[] $nominalPpsMineParameters
 * @property NominalWorkerParameter[] $nominalWorkerParameters
 * @property Asmtp $asmtp
 * @property Object $object
 * @property SensorType $sensorType
 * @property SensorConnectString[] $sensorConnectStrings
 * @property SensorFunction[] $sensorFunctions
 * @property SensorParameter[] $sensorParameters
 * @property StrataMain[] $strataMains
 * @property TextMessage[] $textMessages
 * @property TextMessage[] $textMessages0
 */
class Sensor extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'sensor';
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
            [['id', 'title', 'sensor_type_id', 'asmtp_id', 'object_id'], 'required'],
            [['id', 'sensor_type_id', 'asmtp_id', 'object_id'], 'integer'],
            [['title'], 'string', 'max' => 255],
            [['title'], 'unique'],
            [['id'], 'unique'],
            [['asmtp_id'], 'exist', 'skipOnError' => true, 'targetClass' => Asmtp::className(), 'targetAttribute' => ['asmtp_id' => 'id']],
            [['object_id'], 'exist', 'skipOnError' => true, 'targetClass' => TypicalObject::className(), 'targetAttribute' => ['object_id' => 'id']],
            [['sensor_type_id'], 'exist', 'skipOnError' => true, 'targetClass' => SensorType::className(), 'targetAttribute' => ['sensor_type_id' => 'id']],
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
            'sensor_type_id' => 'Sensor Type ID',
            'asmtp_id' => 'Asmtp ID',
            'object_id' => 'Object ID',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getConjunctionParameterSensors()
    {
        return $this->hasMany(ConjunctionParameterSensor::className(), ['sensor_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMessageSensors()
    {
        return $this->hasMany(MessageSensor::className(), ['sensor_sender_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getNominalConjunctionParameters()
    {
        return $this->hasMany(NominalConjunctionParameter::className(), ['sensor_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getNominalEnergyMineParameters()
    {
        return $this->hasMany(NominalEnergyMineParameter::className(), ['sensor_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getNominalEquipmentParameters()
    {
        return $this->hasMany(NominalEquipmentParameter::className(), ['sensor_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getNominalFaceParameters()
    {
        return $this->hasMany(NominalFaceParameter::className(), ['sensor_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getNominalOperationParameters()
    {
        return $this->hasMany(NominalOperationParameter::className(), ['sensor_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getNominalOperationRegulationFactParameters()
    {
        return $this->hasMany(NominalOperationRegulationFactParameter::className(), ['sensor_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getNominalOperationRegulationParameters()
    {
        return $this->hasMany(NominalOperationRegulationParameter::className(), ['sensor_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getNominalPlaceParameters()
    {
        return $this->hasMany(NominalPlaceParameter::className(), ['sensor_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getNominalPlastParameters()
    {
        return $this->hasMany(NominalPlastParameter::className(), ['sensor_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getNominalPpsMineParameters()
    {
        return $this->hasMany(NominalPpsMineParameter::className(), ['sensor_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getNominalWorkerParameters()
    {
        return $this->hasMany(NominalWorkerParameter::className(), ['sensor_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAsmtp()
    {
        return $this->hasOne(Asmtp::className(), ['id' => 'asmtp_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getObject()
    {
        return $this->hasOne(TypicalObject::className(), ['id' => 'object_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSensorType()
    {
        return $this->hasOne(SensorType::className(), ['id' => 'sensor_type_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSensorConnectStrings()
    {
        return $this->hasMany(SensorConnectString::className(), ['sensor_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSensorFunctions()
    {
        return $this->hasMany(SensorFunction::className(), ['sensor_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSensorParameters()
    {
        return $this->hasMany(SensorParameter::className(), ['sensor_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStrataMains()
    {
        return $this->hasMany(StrataMain::className(), ['sensor_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTextMessages()
    {
        return $this->hasMany(TextMessage::className(), ['reciever_sensor_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTextMessages0()
    {
        return $this->hasMany(TextMessage::className(), ['sender_sensor_id' => 'id']);
    }
    /**
     * МЕТОДЫ И СВЯЗИ СДЕЛАННЫЕ В РУЧНУЮ
     */
    public function getParameters()
    {
        return $this->hasMany(Parameter::className(), ['id' => 'parameter_id'])->via('sensorParameters');
    }
}
