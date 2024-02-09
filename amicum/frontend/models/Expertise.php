<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "expertise".
 *
 * @property int $id
 * @property int $company_department_id Внешний ключ участка где проводилась экспертиза
 * @property string|null $date_issue Дата когда проводилась экспертиза
 * @property int $status_id Внешний ключ справочника статусов
 * @property string $inventory_number Инвентарный номер объекта
 * @property int $industrial_safety_object_id внешний идентификатор объекта
 * @property int $wear_period срок действия ЭПБ
 * @property string $date_last_expertise Дата последний экспертизы ЭПБ
 * @property string $date_next_expertise Дата следующей экспертизы ЭПБ
 * @property int|null $worker_id Внешний идентификатор ответственного 
 * @property int|null $attachment_id Внешний ключ вложения
 *
 * @property Attachment $attachment
 * @property CompanyDepartment $companyDepartment
 * @property IndustrialSafetyObject $industrialSafetyObject
 * @property Status $status
 * @property Worker $worker
 * @property ExpertiseAttachment[] $expertiseAttachments
 * @property ExpertiseEquipment[] $expertiseEquipments
 * @property Equipment[] $equipment
 * @property ExpertiseHistory[] $expertiseHistories
 */
class Expertise extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'expertise';
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
            [['company_department_id', 'status_id', 'inventory_number', 'industrial_safety_object_id', 'wear_period', 'date_next_expertise'], 'required'],
            [['company_department_id', 'status_id', 'industrial_safety_object_id', 'wear_period', 'worker_id', 'attachment_id'], 'integer'],
            [['date_issue', 'date_last_expertise', 'date_next_expertise'], 'safe'],
            [['inventory_number'], 'string', 'max' => 255],
            [['inventory_number', 'industrial_safety_object_id'], 'unique', 'targetAttribute' => ['inventory_number', 'industrial_safety_object_id']],
            [['attachment_id'], 'exist', 'skipOnError' => true, 'targetClass' => Attachment::className(), 'targetAttribute' => ['attachment_id' => 'id']],
            [['company_department_id'], 'exist', 'skipOnError' => true, 'targetClass' => CompanyDepartment::className(), 'targetAttribute' => ['company_department_id' => 'id']],
            [['industrial_safety_object_id'], 'exist', 'skipOnError' => true, 'targetClass' => IndustrialSafetyObject::className(), 'targetAttribute' => ['industrial_safety_object_id' => 'id']],
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
            'id' => 'ID',
            'company_department_id' => 'Внешний ключ участка где проводилась экспертиза',
            'date_issue' => 'Дата когда проводилась экспертиза',
            'status_id' => 'Внешний ключ справочника статусов',
            'inventory_number' => 'Инвентарный номер объекта',
            'industrial_safety_object_id' => 'внешний идентификатор объекта',
            'wear_period' => 'срок действия ЭПБ',
            'date_last_expertise' => 'Дата последний экспертизы ЭПБ',
            'date_next_expertise' => 'Дата следующей экспертизы ЭПБ',
            'worker_id' => 'Внешний идентификатор ответственного ',
            'attachment_id' => 'Внешний ключ вложения',
        ];
    }

    /**
     * Gets query for [[Attachment]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getAttachment()
    {
        return $this->hasOne(Attachment::className(), ['id' => 'attachment_id']);
    }

    /**
     * Gets query for [[CompanyDepartment]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCompanyDepartment()
    {
        return $this->hasOne(CompanyDepartment::className(), ['id' => 'company_department_id']);
    }

    /**
     * Gets query for [[IndustrialSafetyObject]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getIndustrialSafetyObject()
    {
        return $this->hasOne(IndustrialSafetyObject::className(), ['id' => 'industrial_safety_object_id']);
    }

    /**
     * Gets query for [[Status]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getStatus()
    {
        return $this->hasOne(Status::className(), ['id' => 'status_id']);
    }

    /**
     * Gets query for [[Worker]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getWorker()
    {
        return $this->hasOne(Worker::className(), ['id' => 'worker_id']);
    }

    /**
     * Gets query for [[ExpertiseAttachments]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getExpertiseAttachments()
    {
        return $this->hasMany(ExpertiseAttachment::className(), ['expertise_id' => 'id']);
    }

    /**
     * Gets query for [[ExpertiseEquipments]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getExpertiseEquipments()
    {
        return $this->hasMany(ExpertiseEquipment::className(), ['expertise_id' => 'id']);
    }

    /**
     * Gets query for [[Equipment]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getEquipment()
    {
        return $this->hasMany(Equipment::className(), ['id' => 'equipment_id'])->viaTable('expertise_equipment', ['expertise_id' => 'id']);
    }

    /**
     * Gets query for [[ExpertiseHistories]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getExpertiseHistories()
    {
        return $this->hasMany(ExpertiseHistory::className(), ['expertise_id' => 'id']);
    }
}
