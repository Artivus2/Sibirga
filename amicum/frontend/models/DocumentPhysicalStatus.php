<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "document_physical_status".
 *
 * @property int $id
 * @property int $document_physical_id Внешний идентификатор документа (приказа) по медосмотру
 * @property int $status_id Внешний идентификатор статуса документа
 * @property int $worker_id Внешний идентификатор работника который редактировал документ
 * @property string $date_time_create Дата и время редактирования
 *
 * @property DocumentPhysical $documentPhysical
 * @property Status $status
 * @property Worker $worker
 */
class DocumentPhysicalStatus extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'document_physical_status';
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
            [['document_physical_id', 'status_id', 'worker_id', 'date_time_create'], 'required'],
            [['document_physical_id', 'status_id', 'worker_id'], 'integer'],
            [['date_time_create'], 'safe'],
            [['document_physical_id', 'status_id', 'worker_id', 'date_time_create'], 'unique', 'targetAttribute' => ['document_physical_id', 'status_id', 'worker_id', 'date_time_create']],
            [['document_physical_id'], 'exist', 'skipOnError' => true, 'targetClass' => DocumentPhysical::className(), 'targetAttribute' => ['document_physical_id' => 'id']],
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
            'document_physical_id' => 'Внешний идентификатор документа (приказа) по медосмотру',
            'status_id' => 'Внешний идентификатор статуса документа',
            'worker_id' => 'Внешний идентификатор работника который редактировал документ',
            'date_time_create' => 'Дата и время редактирования',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getDocumentPhysical()
    {
        return $this->hasOne(DocumentPhysical::className(), ['id' => 'document_physical_id']);
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
