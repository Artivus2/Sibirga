<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "zipper_journal".
 *
 * @property int $id ключ журнала молний
 * @property string $title Название молний
 * @property string|null $description Развернутое описание
 * @property int|null $attachment_id ключ вложения
 * @property int|null $worker_id ключ работника
 * @property string|null $date_time дата рассылки сообщения
 *
 * @property Attachment $attachment
 * @property Worker $worker
 * @property ZipperJournalSendStatus[] $zipperJournalSendStatuses
 * @property Worker[] $workers
 */
class ZipperJournal extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'zipper_journal';
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
            [['title'], 'required'],
            [['description'], 'string'],
            [['attachment_id', 'worker_id'], 'integer'],
            [['date_time'], 'safe'],
            [['title'], 'string', 'max' => 255],
            [['attachment_id'], 'exist', 'skipOnError' => true, 'targetClass' => Attachment::className(), 'targetAttribute' => ['attachment_id' => 'id']],
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
            'title' => 'Title',
            'description' => 'Description',
            'attachment_id' => 'Attachment ID',
            'worker_id' => 'Worker ID',
            'date_time' => 'Date Time',
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
     * Gets query for [[Worker]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getWorker()
    {
        return $this->hasOne(Worker::className(), ['id' => 'worker_id']);
    }

    /**
     * Gets query for [[ZipperJournalSendStatuses]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getZipperJournalSendStatuses()
    {
        return $this->hasMany(ZipperJournalSendStatus::className(), ['zipper_journal_id' => 'id']);
    }

    /**
     * Gets query for [[Workers]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getWorkers()
    {
        return $this->hasMany(Worker::className(), ['id' => 'worker_id'])->viaTable('zipper_journal_send_status', ['zipper_journal_id' => 'id']);
    }
}
