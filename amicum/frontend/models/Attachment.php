<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "attachment".
 *
 * @property int $id ключ вложения
 * @property string $path Путь до вложения
 * @property string $title имя файла
 * @property string $attachment_type
 * @property resource $sketch эскиз
 * @property string $date Дата создания
 * @property int $worker_id Внешний идентификатор того кто создал вложение
 * @property string $section_title Раздел в котором было создано вложение
 * @property string $date_modified
 * @property int $USER_BLOB_ID
 *
 * @property Worker $worker
 * @property Briefing[] $briefings
 * @property CheckProtocol[] $checkProtocols
 * @property CompanyDepartmentAttachment[] $companyDepartmentAttachments
 * @property CorrectMeasures[] $correctMeasures
 * @property DocumentAttachment[] $documentAttachments
 * @property DocumentEventPbAttachment[] $documentEventPbAttachments
 * @property DocumentPhysicalAttachment[] $documentPhysicalAttachments
 * @property Expertise[] $expertises
 * @property ExpertiseAttachment[] $expertiseAttachments
 * @property ExpertiseCompanyExpert[] $expertiseCompanyExperts
 * @property ExpertiseHistory[] $expertiseHistories
 * @property FireFightingEquipmentDocuments[] $fireFightingEquipmentDocuments
 * @property InjunctionAttachment[] $injunctionAttachments
 * @property InquiryAttachment[] $inquiryAttachments
 * @property InquiryPb[] $inquiries
 * @property MedReport[] $medReports
 * @property OccupationalIllnessAttachment[] $occupationalIllnessAttachments
 * @property OrderOperationAttachment[] $orderOperationAttachments
 * @property PhysicalAttachment[] $physicalAttachments
 * @property PhysicalScheduleAttachment[] $physicalScheduleAttachments
 * @property SoutAttachment[] $soutAttachments
 */
class Attachment extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'attachment';
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
            [['path'], 'required'],
            [['sketch'], 'string'],
            [['date', 'date_modified'], 'safe'],
            [['worker_id', 'USER_BLOB_ID'], 'integer'],
            [['path', 'title', 'section_title'], 'string', 'max' => 255],
            [['attachment_type'], 'string', 'max' => 45],
            [['worker_id'], 'exist', 'skipOnError' => true, 'targetClass' => Worker::className(), 'targetAttribute' => ['worker_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ключ вложения',
            'path' => 'Путь до вложения',
            'title' => 'имя файла',
            'attachment_type' => 'Attachment Type',
            'sketch' => 'эскиз',
            'date' => 'Дата создания',
            'worker_id' => 'Внешний идентификатор того кто создал вложение',
            'section_title' => 'Раздел в котором было создано вложение',
            'date_modified' => 'Date Modified',
            'USER_BLOB_ID' => 'User Blob ID',
        ];
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
    public function getBriefings()
    {
        return $this->hasMany(Briefing::className(), ['attachment_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCheckProtocols()
    {
        return $this->hasMany(CheckProtocol::className(), ['attachment_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCompanyDepartmentAttachments()
    {
        return $this->hasMany(CompanyDepartmentAttachment::className(), ['attachment_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCorrectMeasures()
    {
        return $this->hasMany(CorrectMeasures::className(), ['attachment_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getDocumentAttachments()
    {
        return $this->hasMany(DocumentAttachment::className(), ['attachment_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getDocumentEventPbAttachments()
    {
        return $this->hasMany(DocumentEventPbAttachment::className(), ['attachment_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getDocumentPhysicalAttachments()
    {
        return $this->hasMany(DocumentPhysicalAttachment::className(), ['attachment_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getExpertises()
    {
        return $this->hasMany(Expertise::className(), ['attachment_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getExpertiseAttachments()
    {
        return $this->hasMany(ExpertiseAttachment::className(), ['attachment_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getExpertiseCompanyExperts()
    {
        return $this->hasMany(ExpertiseCompanyExpert::className(), ['attachment_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getExpertiseHistories()
    {
        return $this->hasMany(ExpertiseHistory::className(), ['attachment_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getFireFightingEquipmentDocuments()
    {
        return $this->hasMany(FireFightingEquipmentDocuments::className(), ['attachment_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getInjunctionAttachments()
    {
        return $this->hasMany(InjunctionAttachment::className(), ['attachment_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getInquiryAttachments()
    {
        return $this->hasMany(InquiryAttachment::className(), ['attachment_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getInquiries()
    {
        return $this->hasMany(InquiryPb::className(), ['id' => 'inquiry_id'])->viaTable('inquiry_attachment', ['attachment_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMedReports()
    {
        return $this->hasMany(MedReport::className(), ['attachment_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOccupationalIllnessAttachments()
    {
        return $this->hasMany(OccupationalIllnessAttachment::className(), ['attachment_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOrderOperationAttachments()
    {
        return $this->hasMany(OrderOperationAttachment::className(), ['attachment_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPhysicalAttachments()
    {
        return $this->hasMany(PhysicalAttachment::className(), ['attachment_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPhysicalScheduleAttachments()
    {
        return $this->hasMany(PhysicalScheduleAttachment::className(), ['attachment_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSoutAttachments()
    {
        return $this->hasMany(SoutAttachment::className(), ['attachment_id' => 'id']);
    }
}
