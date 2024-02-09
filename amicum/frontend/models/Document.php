<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "document".
 *
 * @property int $id Идентификатор текущей таблицы (автоинкрементный)
 * @property int $parent_document_id Вышестоящий(родительский) документ
 * @property string $title Название документа
 * @property string $date_start Дата и время действительности
 * @property string $date_end Дата и время окончания действительности
 * @property int $status_id Внешний идентификатор из списка статусов (актулальный, неактуальный)
 * @property int $vid_document_id ключ вида документа
 * @property string $jsondoc сериализованная строка - хранит наполнение документа
 * @property string $note Примечание
 * @property int $ref_norm_doc_id
 * @property string $date_time_sync дата синхронизации записи
 * @property string $number_document Номер документа
 * @property int $worker_id Кто создал документ
 * @property string $date_deposit Дата внесения
 *
 * @property VidDocument $vidDocument
 * @property Worker $worker
 * @property DocumentAttachment[] $documentAttachments
 * @property DocumentStatus[] $documentStatuses
 * @property Status[] $statuses
 * @property InjunctionViolation[] $injunctionViolations
 * @property ParagraphPb[] $paragraphPbs
 * @property Siz[] $sizs
 */
class Document extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'document';
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
            [['parent_document_id', 'status_id', 'vid_document_id', 'ref_norm_doc_id', 'worker_id'], 'integer'],
            [['title', 'date_start', 'date_end', 'status_id', 'vid_document_id', 'worker_id'], 'required'],
            [['date_start', 'date_end', 'date_time_sync', 'date_deposit'], 'safe'],
            [['jsondoc'], 'string'],
            [['title'], 'string', 'max' => 1000],
            [['note'], 'string', 'max' => 45],
            [['number_document'], 'string', 'max' => 255],
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
            'status_id' => 'Внешний идентификатор из списка статусов (актулальный, неактуальный)',
            'vid_document_id' => 'ключ вида документа',
            'jsondoc' => 'сериализованная строка - хранит наполнение документа',
            'note' => 'Примечание',
            'ref_norm_doc_id' => 'Ref Norm Doc ID',
            'date_time_sync' => 'дата синхронизации записи',
            'number_document' => 'Номер документа',
            'worker_id' => 'Кто создал документ',
            'date_deposit' => 'Дата внесения',
        ];
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
    public function getDocumentAttachments()
    {
        return $this->hasMany(DocumentAttachment::className(), ['document_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getDocumentStatuses()
    {
        return $this->hasMany(DocumentStatus::className(), ['document_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStatuses()
    {
        return $this->hasMany(Status::className(), ['id' => 'status_id'])->viaTable('document_status', ['document_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getInjunctionViolations()
    {
        return $this->hasMany(InjunctionViolation::className(), ['document_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getParagraphPbs()
    {
        return $this->hasMany(ParagraphPb::className(), ['document_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSizs()
    {
        return $this->hasMany(Siz::className(), ['document_id' => 'id']);
    }
}
