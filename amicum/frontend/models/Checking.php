<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "checking".
 *
 * @property int $id Идентификатор текущей таблицы (автоинкрементный)\\\\n
 * @property string $title Название проверки\\\\n
 * @property string $date_time_start Дата и время начало проверки\\\\n
 * @property string $date_time_end Дата и время окончания проверки
 * @property int $checking_type_id Внешний ключ тип проверки
 * @property int $company_department_id
 * @property int $instruct_id внешний ключ предписания
 * @property int $kind_document_id внешний ключ вида документа
 * @property string $date_time_sync дата синхронизации
 * @property string $date_time_create дата создания записи
 * @property string $pab_id
 * @property string $date_time_sync_pab
 * @property string $rostex_number
 * @property string $date_time_sync_rostex Дата и время синхронизации РТН
 * @property string $nn_id
 * @property string $date_time_sync_nn
 *
 * @property Audit[] $audits
 * @property CheckingType $checkingType
 * @property CompanyDepartment $companyDepartment
 * @property CheckingPlace[] $checkingPlaces
 * @property CheckingWorkerType[] $checkingWorkerTypes
 * @property Injunction[] $injunctions
 */
class Checking extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'checking';
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
            [['title', 'date_time_start', 'checking_type_id', 'company_department_id'], 'required'],
            [['date_time_create', 'date_time_start', 'date_time_end', 'date_time_sync', 'date_time_sync_pab', 'date_time_sync_rostex', 'date_time_sync_nn'], 'safe'],
            [['checking_type_id', 'company_department_id', 'instruct_id', 'kind_document_id'], 'integer'],
            [['title', 'pab_id', 'rostex_number', 'nn_id'], 'string', 'max' => 255],
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
            'id' => 'Идентификатор текущей таблицы (автоинкрементный)\\\\\\\\n',
            'title' => 'Название проверки\\\\\\\\n',
            'date_time_create' => 'Дата и время создания записи\\\\\\\\n',
            'date_time_start' => 'Дата и время начало проверки\\\\\\\\n',
            'date_time_end' => 'Дата и время окончания проверки',
            'checking_type_id' => 'Внешний ключ тип проверки ',
            'company_department_id' => 'Company Department ID',
            'instruct_id' => 'внешний ключ предписания',
            'kind_document_id' => 'внешний ключ вида документа',
            'date_time_sync' => 'дата синхронизации',
            'pab_id' => 'Pab ID',
            'date_time_sync_pab' => 'Date Time Sync Pab',
            'rostex_number' => 'Rostex Number',
            'date_time_sync_rostex' => 'Дата и время синхронизации РТН',
            'nn_id' => 'Nn ID',
            'date_time_sync_nn' => 'Date Time Sync Nn',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAudits()
    {
        return $this->hasMany(Audit::className(), ['checking_id' => 'id']);
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
    public function getKindDocument()
    {
        return $this->hasOne(KindDocument::className(), ['id' => 'kind_document_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCheckingPlaces()
    {
        return $this->hasMany(CheckingPlace::className(), ['checking_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCheckingWorkerTypes()
    {
        return $this->hasMany(CheckingWorkerType::className(), ['checking_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getInjunctions()
    {
        return $this->hasMany(Injunction::className(), ['checking_id' => 'id']);
    }
}
