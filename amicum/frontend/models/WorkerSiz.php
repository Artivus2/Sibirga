<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "worker_siz".
 *
 * @property int $id Идентификатор таблицы(автоинкрементный)
 * @property int $siz_id Внешний ключ к справочнику средств индивидуальной защиты
 * @property int $worker_id Внешний ключ к спраовочнику сотрудников
 * @property string $size Размерный ряд
 * @property int $count_issued_siz Количество выданных средств индивидуальной защиты за раз
 * @property string $date_issue дата выдачи
 * @property string $date_write_off дата списания
 * @property int|null $status_id статус состояния СИЗ (списан выдан)
 * @property int|null $company_department_id ключ департамента в котором числется работник 
 * @property string|null $date_return плановая дата возврата
 *
 * @property Siz $siz
 * @property Status $status
 * @property Worker $worker
 * @property WorkerSizStatus[] $workerSizStatuses
 */
class WorkerSiz extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'worker_siz';
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
            [['siz_id', 'worker_id', 'size', 'count_issued_siz', 'date_issue', 'date_write_off'], 'required'],
            [['siz_id', 'worker_id', 'count_issued_siz', 'status_id', 'company_department_id'], 'integer'],
            [['date_issue', 'date_write_off', 'date_return'], 'safe'],
            [['size'], 'string', 'max' => 45],
            [['siz_id'], 'exist', 'skipOnError' => true, 'targetClass' => Siz::className(), 'targetAttribute' => ['siz_id' => 'id']],
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
            'siz_id' => 'Siz ID',
            'worker_id' => 'Worker ID',
            'size' => 'Size',
            'count_issued_siz' => 'Count Issued Siz',
            'date_issue' => 'Date Issue',
            'date_write_off' => 'Date Write Off',
            'status_id' => 'Status ID',
            'company_department_id' => 'Company Department ID',
            'date_return' => 'Date Return',
        ];
    }

    /**
     * Gets query for [[Siz]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getSiz()
    {
        return $this->hasOne(Siz::className(), ['id' => 'siz_id']);
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
     * Gets query for [[Worker]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getWorker()
    {
        return $this->hasOne(Worker::className(), ['id' => 'worker_id']);
    }

    /**
     * Gets query for [[WorkerSizStatuses]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getWorkerSizStatuses()
    {
        return $this->hasMany(WorkerSizStatus::className(), ['worker_siz_id' => 'id']);
    }
}
