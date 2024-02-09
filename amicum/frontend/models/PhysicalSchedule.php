<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "physical_schedule".
 *
 * @property int $id
 * @property string $date_start Дата начала
 * @property string $date_end Дата окончания
 * @property int $company_department_id Участок 
 * @property int $physical_id Ключ справочника, к которому относится данный график
 * @property int $physical_kind_id Ключ справочника видов мед.осмотров
 * @property int $day_start День начала
 * @property int $day_end День окончания в графике
 *
 * @property CompanyDepartment $companyDepartment
 * @property Physical $physical
 * @property PhysicalKind $physicalKind
 * @property PhysicalScheduleAttachment[] $physicalScheduleAttachments
 * @property PhysicalWorker[] $physicalWorkers
 */
class PhysicalSchedule extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'physical_schedule';
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
            [['date_start', 'date_end', 'company_department_id', 'physical_id', 'physical_kind_id'], 'required'],
            [['date_start', 'date_end'], 'safe'],
            [['company_department_id', 'physical_id', 'physical_kind_id', 'day_start', 'day_end'], 'integer'],
            [['company_department_id'], 'exist', 'skipOnError' => true, 'targetClass' => CompanyDepartment::className(), 'targetAttribute' => ['company_department_id' => 'id']],
            [['physical_id'], 'exist', 'skipOnError' => true, 'targetClass' => Physical::className(), 'targetAttribute' => ['physical_id' => 'id']],
            [['physical_kind_id'], 'exist', 'skipOnError' => true, 'targetClass' => PhysicalKind::className(), 'targetAttribute' => ['physical_kind_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'date_start' => 'Дата начала',
            'date_end' => 'Дата окончания',
            'company_department_id' => 'Участок ',
            'physical_id' => 'Ключ справочника, к которому относится данный график',
            'physical_kind_id' => 'Ключ справочника видов мед.осмотров',
            'day_start' => 'День начала',
            'day_end' => 'День окончания в графике',
        ];
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
    public function getPhysical()
    {
        return $this->hasOne(Physical::className(), ['id' => 'physical_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPhysicalKind()
    {
        return $this->hasOne(PhysicalKind::className(), ['id' => 'physical_kind_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPhysicalScheduleAttachment()
    {
        return $this->hasOne(PhysicalScheduleAttachment::className(), ['physical_schedule_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPhysicalWorkers()
    {
        return $this->hasMany(PhysicalWorker::className(), ['physical_schedule_id' => 'id']);
    }
}
