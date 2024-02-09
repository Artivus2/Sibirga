<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "passport".
 *
 * @property int $id Ключ справочника паспартов
 * @property string $title Название паспорта
 * @property int $place_id Внешний ключ справочника мест
 *
 * @property ConfigurationFace[] $configurationFaces
 * @property OrderPlace[] $orderPlaces
 * @property OrderTemplatePlace[] $orderTemplatePlaces
 * @property Place $place
 * @property PassportAttachment[] $passportAttachments
 * @property PassportCyclegrammLava[] $passportCyclegrammLavas
 * @property PassportGroupOperation[] $passportGroupOperations
 * @property GroupOperation[] $groupOperations
 * @property PassportObject[] $passportObjects
 * @property PassportOperation[] $passportOperations
 * @property PassportParameter[] $passportParameters
 * @property Parameter[] $parameters
 * @property PassportSketch[] $passportSketches
 * @property Status[] $statuses
 */
class Passport extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'passport';
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
            [['title', 'place_id'], 'required'],
            [['place_id'], 'integer'],
            [['title'], 'string', 'max' => 255],
            [['title'], 'unique'],
            [['title', 'place_id'], 'unique', 'targetAttribute' => ['title', 'place_id']],
            [['place_id'], 'exist', 'skipOnError' => true, 'targetClass' => Place::className(), 'targetAttribute' => ['place_id' => 'id']],
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
            'place_id' => 'Place ID',
        ];
    }

    /**
     * Gets query for [[ConfigurationFaces]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getConfigurationFaces()
    {
        return $this->hasMany(ConfigurationFace::className(), ['passport_id' => 'id']);
    }

    /**
     * Gets query for [[OrderPlaces]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getOrderPlaces()
    {
        return $this->hasMany(OrderPlace::className(), ['passport_id' => 'id']);
    }

    /**
     * Gets query for [[OrderTemplatePlaces]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getOrderTemplatePlaces()
    {
        return $this->hasMany(OrderTemplatePlace::className(), ['passport_id' => 'id']);
    }

    /**
     * Gets query for [[Place]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getPlace()
    {
        return $this->hasOne(Place::className(), ['id' => 'place_id']);
    }

    /**
     * Gets query for [[PassportAttachments]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getPassportAttachments()
    {
        return $this->hasMany(PassportAttachment::className(), ['passport_id' => 'id']);
    }

    /**
     * Gets query for [[PassportCyclegrammLavas]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getPassportCyclegrammLavas()
    {
        return $this->hasMany(PassportCyclegrammLava::className(), ['passport_id' => 'id']);
    }

    /**
     * Gets query for [[PassportGroupOperations]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getPassportGroupOperations()
    {
        return $this->hasMany(PassportGroupOperation::className(), ['passport_id' => 'id']);
    }

    /**
     * Gets query for [[GroupOperations]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getGroupOperations()
    {
        return $this->hasMany(GroupOperation::className(), ['id' => 'group_operation_id'])->viaTable('passport_group_operation', ['passport_id' => 'id']);
    }

    /**
     * Gets query for [[PassportObjects]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getPassportObjects()
    {
        return $this->hasMany(PassportObject::className(), ['passport_id' => 'id']);
    }

    /**
     * Gets query for [[PassportOperations]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getPassportOperations()
    {
        return $this->hasMany(PassportOperation::className(), ['passport_id' => 'id']);
    }

    /**
     * Gets query for [[PassportParameters]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getPassportParameters()
    {
        return $this->hasMany(PassportParameter::className(), ['passport_id' => 'id']);
    }

    /**
     * Gets query for [[Parameters]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getParameters()
    {
        return $this->hasMany(Parameter::className(), ['id' => 'parameter_id'])->viaTable('passport_parameter', ['passport_id' => 'id']);
    }

    /**
     * Gets query for [[PassportSketches]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getPassportSketches()
    {
        return $this->hasMany(PassportSketch::className(), ['passport_id' => 'id']);
    }

    /**
     * Gets query for [[Statuses]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getStatuses()
    {
        return $this->hasMany(Status::className(), ['id' => 'status_id'])->viaTable('passport_sketch', ['passport_id' => 'id']);
    }
}
