<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "event".
 *
 * @property int $id
 * @property string $title
 * @property int $object_id
 *
 * @property Object $object
 * @property EventJournal[] $eventJournals
 * @property EventSituation[] $eventSituations
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
 * @property NominalSensorParameter[] $nominalSensorParameters
 * @property NominalTypeObjectParameter[] $nominalTypeObjectParameters
 * @property StopFace[] $stopFaces
 * @property XmlConfig[] $xmlConfigs
 */
class Event extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'event';
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
            [['id', 'title', 'object_id'], 'required'],
            [['id', 'object_id'], 'integer'],
            [['title'], 'string', 'max' => 255],
            [['title'], 'unique'],
            [['id'], 'unique'],
            [['object_id'], 'exist', 'skipOnError' => true, 'targetClass' => TypicalObject::className(), 'targetAttribute' => ['object_id' => 'id']],
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
            'object_id' => 'Object ID',
        ];
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
    public function getEventJournals()
    {
        return $this->hasMany(EventJournal::className(), ['event_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getEventSituations()
    {
        return $this->hasMany(EventSituation::className(), ['event_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getNominalConjunctionParameters()
    {
        return $this->hasMany(NominalConjunctionParameter::className(), ['event_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getNominalEnergyMineParameters()
    {
        return $this->hasMany(NominalEnergyMineParameter::className(), ['event_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getNominalEquipmentParameters()
    {
        return $this->hasMany(NominalEquipmentParameter::className(), ['event_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getNominalFaceParameters()
    {
        return $this->hasMany(NominalFaceParameter::className(), ['event_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getNominalOperationParameters()
    {
        return $this->hasMany(NominalOperationParameter::className(), ['event_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getNominalOperationRegulationFactParameters()
    {
        return $this->hasMany(NominalOperationRegulationFactParameter::className(), ['event_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getNominalOperationRegulationParameters()
    {
        return $this->hasMany(NominalOperationRegulationParameter::className(), ['event_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getNominalPlaceParameters()
    {
        return $this->hasMany(NominalPlaceParameter::className(), ['event_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getNominalPlastParameters()
    {
        return $this->hasMany(NominalPlastParameter::className(), ['event_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getNominalPpsMineParameters()
    {
        return $this->hasMany(NominalPpsMineParameter::className(), ['event_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getNominalSensorParameters()
    {
        return $this->hasMany(NominalSensorParameter::className(), ['event_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getNominalTypeObjectParameters()
    {
        return $this->hasMany(NominalTypeObjectParameter::className(), ['event_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStopFaces()
    {
        return $this->hasMany(StopFace::className(), ['event_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getXmlConfigs()
    {
        return $this->hasMany(XmlConfig::className(), ['event_id' => 'id']);
    }
}
