<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "update_archive_worker".
 *
 * @property int $id
 * @property int $worker_id ключ работника
 * @property int $update_archive_id ключ обновления
 * @property int $status_id статус прочтения обновления
 *
 * @property Status $status
 * @property UpdateArchive $updateArchive
 * @property Worker $worker
 */
class UpdateArchiveWorker extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'update_archive_worker';
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
            [['worker_id', 'update_archive_id', 'status_id'], 'required'],
            [['worker_id', 'update_archive_id', 'status_id'], 'integer'],
            [['status_id'], 'exist', 'skipOnError' => true, 'targetClass' => Status::className(), 'targetAttribute' => ['status_id' => 'id']],
            [['update_archive_id'], 'exist', 'skipOnError' => true, 'targetClass' => UpdateArchive::className(), 'targetAttribute' => ['update_archive_id' => 'id']],
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
            'worker_id' => 'Worker ID',
            'update_archive_id' => 'Update Archive ID',
            'status_id' => 'Status ID',
        ];
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
     * Gets query for [[UpdateArchive]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getUpdateArchive()
    {
        return $this->hasOne(UpdateArchive::className(), ['id' => 'update_archive_id']);
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
}
