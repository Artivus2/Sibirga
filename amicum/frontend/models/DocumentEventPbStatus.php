<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "document_event_pb_status".
 *
 * @property int $id
 * @property int $document_event_pb_id ключ документа
 * @property int $status_id статус документа
 * @property int $worker_id ключ согласовавшего работника
 * @property string $date_time_create
 *
 * @property DocumentEventPb $documentEventPb
 * @property Status $status
 * @property Worker $worker
 */
class DocumentEventPbStatus extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'document_event_pb_status';
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
            [['document_event_pb_id', 'status_id', 'worker_id', 'date_time_create'], 'required'],
            [['document_event_pb_id', 'status_id', 'worker_id'], 'integer'],
            [['date_time_create'], 'safe'],
            [['document_event_pb_id', 'status_id', 'worker_id', 'date_time_create'], 'unique', 'targetAttribute' => ['document_event_pb_id', 'status_id', 'worker_id', 'date_time_create']],
            [['document_event_pb_id'], 'exist', 'skipOnError' => true, 'targetClass' => DocumentEventPb::className(), 'targetAttribute' => ['document_event_pb_id' => 'id']],
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
            'document_event_pb_id' => 'ключ документа',
            'status_id' => 'статус документа',
            'worker_id' => 'ключ согласовавшего работника',
            'date_time_create' => 'Date Time Create',
        ];
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
}
