<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "document_physical".
 *
 * @property int $id
 * @property int $physical_id
 * @property int $document_event_pb_id Внешний идентификатор документа 
 * @property int $company_department_id Внешний идентификатор участка на который сохранён документ
 *
 * @property CompanyDepartment $companyDepartment
 * @property DocumentEventPb $documentEventPb
 * @property Physical $physical
 * @property DocumentPhysicalAttachment[] $documentPhysicalAttachments
 * @property DocumentPhysicalStatus[] $documentPhysicalStatuses
 */
class DocumentPhysical extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'document_physical';
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
            [['physical_id', 'document_event_pb_id'], 'required'],
            [['physical_id', 'document_event_pb_id', 'company_department_id'], 'integer'],
            [['company_department_id'], 'exist', 'skipOnError' => true, 'targetClass' => CompanyDepartment::className(), 'targetAttribute' => ['company_department_id' => 'id']],
            [['document_event_pb_id'], 'exist', 'skipOnError' => true, 'targetClass' => DocumentEventPb::className(), 'targetAttribute' => ['document_event_pb_id' => 'id']],
            [['physical_id'], 'exist', 'skipOnError' => true, 'targetClass' => Physical::className(), 'targetAttribute' => ['physical_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'physical_id' => 'Physical ID',
            'document_event_pb_id' => 'Внешний идентификатор документа ',
            'company_department_id' => 'Внешний идентификатор участка на который сохранён документ',
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
    public function getDocumentEventPb()
    {
        return $this->hasOne(DocumentEventPb::className(), ['id' => 'document_event_pb_id']);
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
    public function getDocumentPhysicalAttachments()
    {
        return $this->hasMany(DocumentPhysicalAttachment::className(), ['document_physical_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getDocumentPhysicalStatuses()
    {
        return $this->hasMany(DocumentPhysicalStatus::className(), ['document_physical_id' => 'id']);
    }
}
