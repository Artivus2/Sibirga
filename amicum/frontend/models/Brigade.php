<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "brigade".
 *
 * @property int $id
 * @property string $description
 * @property string $date_time
 * @property int $brigader_id
 * @property int $company_department_id
 * @property int $status_id
 *
 * @property CompanyDepartment $companyDepartment
 * @property Status $status
 * @property Worker $brigader
 * @property BrigadeParameter[] $brigadeParameters
 * @property BrigadeWorker[] $brigadeWorkers
 * @property Chane[] $chanes
 * @property ConfigurationFace[] $configurationFaces
 * @property GraphicRepair[] $graphicRepairs
 * @property OperationWorker[] $operationWorkers
 * @property Planogramma[] $planogrammas
 * @property RepairMapSpecific[] $repairMapSpecifics
 */
class Brigade extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'brigade';
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
            [['description', 'date_time', 'brigader_id', 'company_department_id', 'status_id'], 'required'],
            [['description'], 'string', 'max' => 255],
            [['date_time'], 'safe'],
            [['brigader_id', 'company_department_id', 'status_id'], 'integer'],
            [['date_time', 'brigader_id', 'company_department_id'], 'unique', 'targetAttribute' => ['description', 'company_department_id']],
            [['company_department_id'], 'exist', 'skipOnError' => true, 'targetClass' => CompanyDepartment::className(), 'targetAttribute' => ['company_department_id' => 'id']],
            [['status_id'], 'exist', 'skipOnError' => true, 'targetClass' => Status::className(), 'targetAttribute' => ['status_id' => 'id']],
            [['brigader_id'], 'exist', 'skipOnError' => true, 'targetClass' => Worker::className(), 'targetAttribute' => ['brigader_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'description' => 'Description',
            'date_time' => 'Date Time',
            'brigader_id' => 'Brigader ID',
            'company_department_id' => 'Company Department ID',
            'status_id' => 'Status ID',
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
    public function getStatus()
    {
        return $this->hasOne(Status::className(), ['id' => 'status_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getBrigader()
    {
        return $this->hasOne(Worker::className(), ['id' => 'brigader_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getBrigadeParameters()
    {
        return $this->hasMany(BrigadeParameter::className(), ['brigade_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getBrigadeWorkers()
    {
        return $this->hasMany(BrigadeWorker::className(), ['brigade_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getChanes()
    {
        return $this->hasMany(Chane::className(), ['brigade_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getConfigurationFaces()
    {
        return $this->hasMany(ConfigurationFace::className(), ['brigade_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getGraphicRepairs()
    {
        return $this->hasMany(GraphicRepair::className(), ['brigade_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOperationWorkers()
    {
        return $this->hasMany(OperationWorker::className(), ['brigade_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPlanogrammas()
    {
        return $this->hasMany(Planogramma::className(), ['brigade_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRepairMapSpecifics()
    {
        return $this->hasMany(RepairMapSpecific::className(), ['brigade_id' => 'id']);
    }
}
