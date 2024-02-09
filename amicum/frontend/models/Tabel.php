<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "tabel".
 *
 * @property int $id
 * @property int $month
 * @property int $year
 * @property int $version_tabel
 * @property int $approved
 * @property int $company_department_id
 *
 * @property FactTabelWorker[] $factTabelWorkers
 * @property CompanyDepartment $companyDepartment
 * @property TabelWorker[] $tabelWorkers
 */
class Tabel extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'tabel';
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
            [['month', 'year', 'version_tabel', 'approved', 'company_department_id'], 'required'],
            [['month', 'year', 'version_tabel', 'approved', 'company_department_id'], 'integer'],
            [['month', 'year', 'version_tabel'], 'unique', 'targetAttribute' => ['month', 'year', 'version_tabel']],
            [['company_department_id'], 'exist', 'skipOnError' => true, 'targetClass' => CompanyDepartment::className(), 'targetAttribute' => ['company_department_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'month' => 'Month',
            'year' => 'Year',
            'version_tabel' => 'Version Tabel',
            'approved' => 'Approved',
            'company_department_id' => 'Company Department ID',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getFactTabelWorkers()
    {
        return $this->hasMany(FactTabelWorker::className(), ['tabel_id' => 'id']);
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
    public function getTabelWorkers()
    {
        return $this->hasMany(TabelWorker::className(), ['tabel_id' => 'id']);
    }
}
