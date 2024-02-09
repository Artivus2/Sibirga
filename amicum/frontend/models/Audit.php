<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "audit".
 *
 * @property int $id
 * @property int $company_department_id Внешний ключ участка
 * @property string $date_time Дата, на которую запланирован аудит
 * @property int $checking_id
 * @property string $description Примечание к запланированному аудиту
 * @property int $checking_type_id Внешний идентификатор типа проверки
 *
 * @property Checking $checking
 * @property CheckingType $checkingType
 * @property CompanyDepartment $companyDepartment
 * @property AuditPlace[] $auditPlaces
 * @property AuditWorker[] $auditWorkers
 */
class Audit extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'audit';
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
            [['company_department_id', 'date_time'], 'required'],
            [['company_department_id', 'checking_id', 'checking_type_id'], 'integer'],
            [['date_time'], 'safe'],
            [['description'], 'string', 'max' => 255],
            [['checking_id'], 'exist', 'skipOnError' => true, 'targetClass' => Checking::className(), 'targetAttribute' => ['checking_id' => 'id']],
            [['checking_type_id'], 'exist', 'skipOnError' => true, 'targetClass' => CheckingType::className(), 'targetAttribute' => ['checking_type_id' => 'id']],
            [['company_department_id'], 'exist', 'skipOnError' => true, 'targetClass' => CompanyDepartment::className(), 'targetAttribute' => ['company_department_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'company_department_id' => 'Внешний ключ участка',
            'date_time' => 'Дата, на которую запланирован аудит',
            'checking_id' => 'Checking ID',
            'description' => 'Примечание к запланированному аудиту',
            'checking_type_id' => 'Внешний идентификатор типа проверки',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getChecking()
    {
        return $this->hasOne(Checking::className(), ['id' => 'checking_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCheckingType()
    {
        return $this->hasOne(CheckingType::className(), ['id' => 'checking_type_id']);
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
    public function getAuditPlaces()
    {
        return $this->hasMany(AuditPlace::className(), ['audit_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAuditWorkers()
    {
        return $this->hasMany(AuditWorker::className(), ['audit_id' => 'id']);
    }
}
