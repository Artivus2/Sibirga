<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "zipper_journal_send_status".
 *
 * @property int $id ключ статуса доставки молнии
 * @property int $zipper_journal_id ключ журнала молний
 * @property int $company_department_id ключ департамента
 * @property string $date_time дата и время получения молнии
 * @property int $worker_id ключ получателя  молнии
 * @property int $status_id ключ статуса получателя
 *
 * @property ZipperJournal $zipperJournal
 * @property CompanyDepartment $companyDepartment
 * @property Status $status
 * @property Worker $worker
 */
class ZipperJournalSendStatus extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'zipper_journal_send_status';
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
            [['zipper_journal_id', 'company_department_id', 'date_time', 'worker_id', 'status_id'], 'required'],
            [['zipper_journal_id', 'company_department_id', 'worker_id', 'status_id'], 'integer'],
            [['date_time'], 'safe'],
            [['zipper_journal_id', 'worker_id'], 'unique', 'targetAttribute' => ['zipper_journal_id', 'worker_id']],
            [['zipper_journal_id'], 'exist', 'skipOnError' => true, 'targetClass' => ZipperJournal::className(), 'targetAttribute' => ['zipper_journal_id' => 'id']],
            [['company_department_id'], 'exist', 'skipOnError' => true, 'targetClass' => CompanyDepartment::className(), 'targetAttribute' => ['company_department_id' => 'id']],
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
            'id' => 'ключ статуса доставки молнии',
            'zipper_journal_id' => 'ключ журнала молний',
            'company_department_id' => 'ключ департамента',
            'date_time' => 'дата и время получения молнии',
            'worker_id' => 'ключ получателя  молнии',
            'status_id' => 'ключ статуса получателя',
        ];
    }

    /**
     * Gets query for [[ZipperJournal]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getZipperJournal()
    {
        return $this->hasOne(ZipperJournal::className(), ['id' => 'zipper_journal_id']);
    }

    /**
     * Gets query for [[CompanyDepartment]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCompanyDepartment()
    {
        return $this->hasOne(CompanyDepartment::className(), ['id' => 'company_department_id']);
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
}
