<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "injunction".
 *
 * @property int $id Идентификатор текущей таблицы (автоинкрементный)
 * @property int $place_id Внешний ключ местоположения из справочника местоположений 
 * @property int $worker_id Внешний ключ типизировнаного работника(тот кто выдал предписание)
 * @property int $kind_document_id Внешний ключ вида документа
 * @property int $rtn_statistic_status_id Внешний ключ статуса из справочника статусов. Указывется, на то, что указать ли предписание в статистике РТН или нет.
 * @property int $checking_id Внешний ключ проверки из справочника проверок
 * @property string $description Детальное описание предписания
 * @property int $status_id Внешний ключ статуса (есть нарушение или нет)
 * @property int $observation_number Номер наблюдения
 * @property int $company_department_id Внешний идентификатор участка ответственного
 * @property int $instruct_id_ip ключ пункта предписания sap
 * @property string $date_time_sync дата синхронизации
 * @property int $instruct_rtn_id
 * @property string $date_time_sync_rostex
 * @property string $instruct_pab_id Ключ ПАБа
 * @property string $date_time_sync_pab Дата и время синхронизации
 * @property string $instruct_nn_id
 * @property string $date_time_sync_nn
 *
 * @property Checking $checking
 * @property CompanyDepartment $companyDepartment
 * @property KindDocument $kindDocument
 * @property Place $place
 * @property Status $rtnStatisticStatus
 * @property Status $status
 * @property Worker $worker
 * @property InjunctionAttachment[] $injunctionAttachments
 * @property InjunctionStatus[] $injunctionStatuses
 * @property InjunctionViolation[] $injunctionViolations
 */
class Injunction extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'injunction';
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
            [['place_id', 'worker_id', 'kind_document_id', 'rtn_statistic_status_id', 'checking_id', 'status_id', 'observation_number', 'company_department_id'], 'required'],
            [['place_id', 'worker_id', 'kind_document_id', 'rtn_statistic_status_id', 'checking_id', 'status_id', 'observation_number', 'company_department_id', 'instruct_id_ip', 'instruct_rtn_id'], 'integer'],
            [['date_time_sync', 'date_time_sync_rostex', 'date_time_sync_pab', 'date_time_sync_nn'], 'safe'],
            [['description'], 'string', 'max' => 805],
            [['instruct_pab_id', 'instruct_nn_id'], 'string', 'max' => 255],
            [['place_id', 'worker_id', 'kind_document_id', 'checking_id', 'observation_number'], 'unique', 'targetAttribute' => ['place_id', 'worker_id', 'kind_document_id', 'checking_id', 'observation_number']],
            [['checking_id'], 'exist', 'skipOnError' => true, 'targetClass' => Checking::className(), 'targetAttribute' => ['checking_id' => 'id']],
            [['company_department_id'], 'exist', 'skipOnError' => true, 'targetClass' => CompanyDepartment::className(), 'targetAttribute' => ['company_department_id' => 'id']],
            [['kind_document_id'], 'exist', 'skipOnError' => true, 'targetClass' => KindDocument::className(), 'targetAttribute' => ['kind_document_id' => 'id']],
            [['place_id'], 'exist', 'skipOnError' => true, 'targetClass' => Place::className(), 'targetAttribute' => ['place_id' => 'id']],
            [['rtn_statistic_status_id'], 'exist', 'skipOnError' => true, 'targetClass' => Status::className(), 'targetAttribute' => ['rtn_statistic_status_id' => 'id']],
            [['status_id'], 'exist', 'skipOnError' => true, 'targetClass' => Status::className(), 'targetAttribute' => ['status_id' => 'id']],
            [['worker_id'], 'exist', 'skipOnError' => true, 'targetClass' => Worker::className(), 'targetAttribute' => ['worker_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'Идентификатор текущей таблицы (автоинкрементный)',
            'place_id' => 'Внешний ключ местоположения из справочника местоположений ',
            'worker_id' => 'Внешний ключ типизировнаного работника(тот кто выдал предписание)',
            'kind_document_id' => 'Внешний ключ вида документа',
            'rtn_statistic_status_id' => 'Внешний ключ статуса из справочника статусов. Указывется, на то, что указать ли предписание в статистике РТН или нет.',
            'checking_id' => 'Внешний ключ проверки из справочника проверок',
            'description' => 'Детальное описание предписания',
            'status_id' => 'Внешний ключ статуса (есть нарушение или нет)',
            'observation_number' => 'Номер наблюдения',
            'company_department_id' => 'Внешний идентификатор участка ответственного',
            'instruct_id_ip' => 'ключ пункта предписания sap',
            'date_time_sync' => 'дата синхронизации',
            'instruct_rtn_id' => 'Instruct Rtn ID',
            'date_time_sync_rostex' => 'Date Time Sync Rostex',
            'instruct_pab_id' => 'Ключ ПАБа',
            'date_time_sync_pab' => 'Дата и время синхронизации',
            'instruct_nn_id' => 'Instruct Nn ID',
            'date_time_sync_nn' => 'Date Time Sync Nn',
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
    public function getPlace()
    {
        return $this->hasOne(Place::className(), ['id' => 'place_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPlace1()
    {
        return $this->hasOne(Place::className(), ['id' => 'place_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRtnStatisticStatus()
    {
        return $this->hasOne(Status::className(), ['id' => 'rtn_statistic_status_id']);
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
    public function getWorker()
    {
        return $this->hasOne(Worker::className(), ['id' => 'worker_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getInjunctionAttachments()
    {
        return $this->hasMany(InjunctionAttachment::className(), ['injunction_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getInjunctionStatuses()
    {
        return $this->hasMany(InjunctionStatus::className(), ['injunction_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getInjunctionViolations()
    {
        return $this->hasMany(InjunctionViolation::className(), ['injunction_id' => 'id']);
    }


    // !!!!! Добавлена вручную первый статус предписания по дате  !!!!!!!
    public function getFirstInjunctionStatuses()
    {
        return $this->hasOne(InjunctionStatus::className(), ['injunction_id' => 'id'])->orderBy('injunction_status.date_time ASC');
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getInjunctionAttachment()
    {
        return $this->hasMany(InjunctionAttachment::className(), ['injunction_id' => 'id']);
    }


    // Добавлена вручную последний статус предписания по дате
    public function getLastInjunctionStatuses()
    {
        return $this->hasOne(InjunctionStatus::className(), ['injunction_id' => 'id'])->orderBy('injunction_status.date_time DESC')->limit(1);
    }
}
