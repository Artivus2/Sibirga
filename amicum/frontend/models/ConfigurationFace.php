<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "configuration_face".
 *
 * @property int $id
 * @property int $face_id
 * @property string $date_time
 * @property string $title
 * @property int $status_id
 * @property int $brigade_id
 * @property int $place_id
 * @property int $passport_id
 * @property int $company_department_id Внешний ключ связки подразделения к предприятию
 *
 * @property Brigade $brigade
 * @property CompanyDepartment $companyDepartment
 * @property Face $face
 * @property Passport $passport
 * @property Place $place
 * @property Status $status
 * @property ConfigurationFaceEquipment[] $configurationFaceEquipments
 */
class ConfigurationFace extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'configuration_face';
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
            [['face_id', 'date_time', 'title', 'status_id', 'brigade_id', 'place_id', 'passport_id', 'company_department_id'], 'required'],
            [['face_id', 'status_id', 'brigade_id', 'place_id', 'passport_id', 'company_department_id'], 'integer'],
            [['date_time'], 'safe'],
            [['title'], 'string', 'max' => 255],
            [['brigade_id'], 'exist', 'skipOnError' => true, 'targetClass' => Brigade::className(), 'targetAttribute' => ['brigade_id' => 'id']],
            [['company_department_id'], 'exist', 'skipOnError' => true, 'targetClass' => CompanyDepartment::className(), 'targetAttribute' => ['company_department_id' => 'id']],
            [['face_id'], 'exist', 'skipOnError' => true, 'targetClass' => Face::className(), 'targetAttribute' => ['face_id' => 'id']],
            [['passport_id'], 'exist', 'skipOnError' => true, 'targetClass' => Passport::className(), 'targetAttribute' => ['passport_id' => 'id']],
            [['place_id'], 'exist', 'skipOnError' => true, 'targetClass' => Place::className(), 'targetAttribute' => ['place_id' => 'id']],
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
            'face_id' => 'Face ID',
            'date_time' => 'Date Time',
            'title' => 'Title',
            'status_id' => 'Status ID',
            'brigade_id' => 'Brigade ID',
            'place_id' => 'Place ID',
            'passport_id' => 'Passport ID',
            'company_department_id' => 'Внешний ключ связки подразделения к предприятию',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getBrigade()
    {
        return $this->hasOne(Brigade::className(), ['id' => 'brigade_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCompanyDepartment()
    {
        return $this->hasOne(CompanyDepartment::className(), ['id' => 'company_department_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getFace()
    {
        return $this->hasOne(Face::className(), ['id' => 'face_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPassport()
    {
        return $this->hasOne(Passport::className(), ['id' => 'passport_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPlace()
    {
        return $this->hasOne(Place::className(), ['id' => 'place_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStatus()
    {
        return $this->hasOne(Status::className(), ['id' => 'status_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getConfigurationFaceEquipments()
    {
        return $this->hasMany(ConfigurationFaceEquipment::className(), ['configuration_face_id' => 'id']);
    }
}
