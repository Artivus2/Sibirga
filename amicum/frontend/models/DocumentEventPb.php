<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "document_event_pb".
 *
 * @property int $id Идентификатор текущей таблицы (автоинкрементный)
 * @property int $parent_document_id Вышестоящий(родительский) документ
 * @property string $title Название документа
 * @property string $date_start Дата и время действительности
 * @property string $date_end Дата и время окончания действительности
 * @property int $last_status_id Внешний идентификатор из списка статусов (актулальный, неактуальный) - последний статус
 * @property int $vid_document_id ключ вида документа
 * @property string $jsondoc сериализованная строка - хранит наполнение документа
 * @property int $worker_id ключ последнего согласовавшего документ
 * @property string $number_document Номер документа
 *
 * @property Status $lastStatus
 * @property VidDocument $vidDocument
 * @property Worker $worker
 * @property DocumentEventPbAttachment[] $documentEventPbAttachments
 * @property DocumentEventPbStatus[] $documentEventPbStatuses
 * @property InquiryDocument[] $inquiryDocuments
 * @property InquiryPb[] $inquiries
 */
class DocumentEventPb extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'document_event_pb';
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
            [['parent_document_id', 'last_status_id', 'vid_document_id', 'worker_id'], 'integer'],
            [['title', 'date_start', 'date_end', 'last_status_id', 'vid_document_id'], 'required'],
            [['date_start', 'date_end'], 'safe'],
            [['jsondoc'], 'string'],
            [['title'], 'string', 'max' => 1000],
            [['number_document'], 'string', 'max' => 255],
            [['last_status_id'], 'exist', 'skipOnError' => true, 'targetClass' => Status::className(), 'targetAttribute' => ['last_status_id' => 'id']],
            [['vid_document_id'], 'exist', 'skipOnError' => true, 'targetClass' => VidDocument::className(), 'targetAttribute' => ['vid_document_id' => 'id']],
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
            'parent_document_id' => 'Вышестоящий(родительский) документ',
            'title' => 'Название документа',
            'date_start' => 'Дата и время действительности',
            'date_end' => 'Дата и время окончания действительности',
            'last_status_id' => 'Внешний идентификатор из списка статусов (актулальный, неактуальный) - последний статус',
            'vid_document_id' => 'ключ вида документа',
            'jsondoc' => 'сериализованная строка - хранит наполнение документа',
            'worker_id' => 'ключ последнего согласовавшего документ',
            'number_document' => 'Номер документа',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getLastStatus()
    {
        return $this->hasOne(Status::className(), ['id' => 'last_status_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getVidDocument()
    {
        return $this->hasOne(VidDocument::className(), ['id' => 'vid_document_id']);
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
    public function getDocumentEventPbAttachments()
    {
        return $this->hasMany(DocumentEventPbAttachment::className(), ['document_event_pb_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getDocumentEventPbStatuses()
    {
        return $this->hasMany(DocumentEventPbStatus::className(), ['document_event_pb_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getInquiryDocuments()
    {
        return $this->hasMany(InquiryDocument::className(), ['document_event_pb_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getInquiries()
    {
        return $this->hasMany(InquiryPb::className(), ['id' => 'inquiry_id'])->viaTable('inquiry_document', ['document_event_pb_id' => 'id']);
    }
}
