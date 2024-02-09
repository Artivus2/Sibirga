<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "company_department_worker_vgk".
 *
 * @property int $id
 * @property int $company_department_id Внешний идентификатор участка на котором находиться ВГК
 * @property string $date Дата назначения
 * @property int $worker_id Внешний идентификатор работника который является членом ВГК
 *
 * @property CompanyDepartment $companyDepartment
 * @property Worker $worker
 */
class CompanyDepartmentWorkerVgk extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'company_department_worker_vgk';
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
            [['company_department_id', 'date', 'worker_id'], 'required'],
            [['company_department_id', 'worker_id'], 'integer'],
            [['date'], 'safe'],
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
            'company_department_id' => 'Внешний идентификатор участка на котором находиться ВГК',
            'date' => 'Дата назначения',
            'worker_id' => 'Внешний идентификатор работника который является членом ВГК',
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
    public function getWorker()
    {
        return $this->hasOne(Worker::className(), ['id' => 'worker_id']);
    }
}
