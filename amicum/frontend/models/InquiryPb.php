<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "inquiry_pb".
 *
 * @property int $id
 * @property string $date_time_create Дата и время создания расследования
 * @property int $case_pb_id ключ обстоятельства
 * @property string $description_event_pb описание обстоятельства
 * @property int $worker_id ключ работника создавшего в БД данное сообщение о несчастном случае
 * @property string $date_time_event Дата и время события/несчастного случая
 * @property int $company_department_id
 *
 * @property EventPb[] $eventPbs
 * @property InquiryAttachment[] $inquiryAttachments
 * @property Attachment[] $attachments
 * @property InquiryDocument[] $inquiryDocuments
 * @property DocumentEventPb[] $documentEventPbs
 * @property CasePb $casePb
 * @property CompanyDepartment $companyDepartment
 * @property Worker $worker
 */
class InquiryPb extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'inquiry_pb';
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
            [['date_time_create', 'case_pb_id', 'worker_id', 'date_time_event', 'company_department_id'], 'required'],
            [['date_time_create', 'date_time_event'], 'safe'],
            [['case_pb_id', 'worker_id', 'company_department_id'], 'integer'],
            [['description_event_pb'], 'string', 'max' => 900],
            [['case_pb_id'], 'exist', 'skipOnError' => true, 'targetClass' => CasePb::className(), 'targetAttribute' => ['case_pb_id' => 'id']],
            [['company_department_id'], 'exist', 'skipOnError' => true, 'targetClass' => CompanyDepartment::className(), 'targetAttribute' => ['company_department_id' => 'id']],
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
            'date_time_create' => 'Дата и время создания расследования',
            'case_pb_id' => 'ключ обстоятельства',
            'description_event_pb' => 'описание обстоятельства',
            'worker_id' => 'ключ работника создавшего в БД данное сообщение о несчастном случае',
            'date_time_event' => 'Дата и время события/несчастного случая',
            'company_department_id' => 'Company Department ID',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getEventPbs()
    {
        return $this->hasMany(EventPb::className(), ['inquiry_pb_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getInquiryAttachments()
    {
        return $this->hasMany(InquiryAttachment::className(), ['inquiry_id' => 'id'])->alias('attachment3');
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAttachments()
    {
        return $this->hasMany(Attachment::className(), ['id' => 'attachment_id'])->viaTable('inquiry_attachment', ['inquiry_id' => 'id'])->alias('attachment2');
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getInquiryDocuments()
    {
        return $this->hasMany(InquiryDocument::className(), ['inquiry_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getDocumentEventPbs()
    {
        return $this->hasMany(DocumentEventPb::className(), ['id' => 'document_event_pb_id'])->viaTable('inquiry_document', ['inquiry_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCasePb()
    {
        return $this->hasOne(CasePb::className(), ['id' => 'case_pb_id']);
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
    public function getWorker()
    {
        return $this->hasOne(Worker::className(), ['id' => 'worker_id']);
    }
}
