<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "company_department".
 *
 * @property int $id
 * @property int $department_id
 * @property int $company_id
 * @property int $department_type_id
 *
 * @property Audit[] $audits
 * @property Briefing[] $briefings
 * @property Brigade[] $brigades
 * @property CheckKnowledge[] $checkKnowledges
 * @property Checking[] $checkings
 * @property Company $company
 * @property Department $department
 * @property DepartmentType $departmentType
 * @property CompanyDepartmentAttachment[] $companyDepartmentAttachments
 * @property CompanyDepartmentInfo[] $companyDepartmentInfos
 * @property CompanyDepartmentRoute[] $companyDepartmentRoutes
 * @property CompanyDepartmentWorkerVgk[] $companyDepartmentWorkerVgks
 * @property ConfigurationFace[] $configurationFaces
 * @property Contingent[] $contingents
 * @property ContractingOrganization[] $contractingOrganizations
 * @property DepartmentParameter[] $departmentParameters
 * @property DepartmentParameterSummary[] $departmentParameterSummaries
 * @property DocumentPhysical[] $documentPhysicals
 * @property Expertise[] $expertises
 * @property FireFightingObject[] $fireFightingObjects
 * @property GraficTabelMain[] $graficTabelMains
 * @property GroupDep[] $groupDeps
 * @property Injunction[] $injunctions
 * @property InquiryPb[] $inquiryPbs
 * @property OccupationalIllness[] $occupationalIllnesses
 * @property Order[] $orders
 * @property OrderTemplate[] $orderTemplates
 * @property OrderVtbAb[] $orderVtbAbs
 * @property PhysicalSchedule[] $physicalSchedules
 * @property PlaceCompanyDepartment[] $placeCompanyDepartments
 * @property PlannedSout[] $plannedSouts
 * @property ShiftDepartment[] $shiftDepartments
 * @property SizStore[] $sizStores
 * @property Siz[] $sizs
 * @property Sout[] $souts
 * @property Tabel[] $tabels
 * @property TemplateOrderVtbAb[] $templateOrderVtbAbs
 * @property Worker[] $workers
 * @property WorkingPlace[] $workingPlaces
 */
class CompanyDepartment extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'company_department';
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
            [['department_id', 'company_id'], 'required'],
            [['department_id', 'company_id', 'department_type_id'], 'integer'],
            [['company_id'], 'exist', 'skipOnError' => true, 'targetClass' => Company::className(), 'targetAttribute' => ['company_id' => 'id']],
            [['department_id'], 'exist', 'skipOnError' => true, 'targetClass' => Department::className(), 'targetAttribute' => ['department_id' => 'id']],
            [['department_type_id'], 'exist', 'skipOnError' => true, 'targetClass' => DepartmentType::className(), 'targetAttribute' => ['department_type_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'department_id' => 'Department ID',
            'company_id' => 'Company ID',
            'department_type_id' => 'Department Type ID',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAudits()
    {
        return $this->hasMany(Audit::className(), ['company_department_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getBriefings()
    {
        return $this->hasMany(Briefing::className(), ['company_department_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getBrigades()
    {
        return $this->hasMany(Brigade::className(), ['company_department_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCheckKnowledges()
    {
        return $this->hasMany(CheckKnowledge::className(), ['company_department_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCheckings()
    {
        return $this->hasMany(Checking::className(), ['company_department_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCompany()
    {
        return $this->hasOne(Company::className(), ['id' => 'company_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getDepartment()
    {
        return $this->hasOne(Department::className(), ['id' => 'department_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getDepartmentType()
    {
        return $this->hasOne(DepartmentType::className(), ['id' => 'department_type_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCompanyDepartmentAttachments()
    {
        return $this->hasMany(CompanyDepartmentAttachment::className(), ['company_department_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCompanyDepartmentInfos()
    {
        return $this->hasMany(CompanyDepartmentInfo::className(), ['company_department_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCompanyDepartmentRoutes()
    {
        return $this->hasMany(CompanyDepartmentRoute::className(), ['company_department_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCompanyDepartmentWorkerVgks()
    {
        return $this->hasMany(CompanyDepartmentWorkerVgk::className(), ['company_department_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getConfigurationFaces()
    {
        return $this->hasMany(ConfigurationFace::className(), ['company_department_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getContingents()
    {
        return $this->hasMany(Contingent::className(), ['company_department_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getContractingOrganizations()
    {
        return $this->hasMany(ContractingOrganization::className(), ['company_department_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getDepartmentParameters()
    {
        return $this->hasMany(DepartmentParameter::className(), ['company_department_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getDepartmentParameterSummaries()
    {
        return $this->hasMany(DepartmentParameterSummary::className(), ['company_department_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getDocumentPhysicals()
    {
        return $this->hasMany(DocumentPhysical::className(), ['company_department_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getExpertises()
    {
        return $this->hasMany(Expertise::className(), ['company_department_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getFireFightingObjects()
    {
        return $this->hasMany(FireFightingObject::className(), ['company_department_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getGraficTabelMains()
    {
        return $this->hasMany(GraficTabelMain::className(), ['company_department_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getGroupDeps()
    {
        return $this->hasMany(GroupDep::className(), ['company_department_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getInjunctions()
    {
        return $this->hasMany(Injunction::className(), ['company_department_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getInquiryPbs()
    {
        return $this->hasMany(InquiryPb::className(), ['company_department_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOccupationalIllnesses()
    {
        return $this->hasMany(OccupationalIllness::className(), ['company_department_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOrders()
    {
        return $this->hasMany(Order::className(), ['company_department_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOrderTemplates()
    {
        return $this->hasMany(OrderTemplate::className(), ['company_department_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOrderVtbAbs()
    {
        return $this->hasMany(OrderVtbAb::className(), ['company_department_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPhysicalSchedules()
    {
        return $this->hasMany(PhysicalSchedule::className(), ['company_department_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPlaceCompanyDepartments()
    {
        return $this->hasMany(PlaceCompanyDepartment::className(), ['company_department_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPlannedSouts()
    {
        return $this->hasMany(PlannedSout::className(), ['company_department_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getShiftDepartments()
    {
        return $this->hasMany(ShiftDepartment::className(), ['company_department_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSizStores()
    {
        return $this->hasMany(SizStore::className(), ['company_department_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSizs()
    {
        return $this->hasMany(Siz::className(), ['id' => 'siz_id'])->viaTable('siz_store', ['company_department_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSouts()
    {
        return $this->hasMany(Sout::className(), ['company_department_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTabels()
    {
        return $this->hasMany(Tabel::className(), ['company_department_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTemplateOrderVtbAbs()
    {
        return $this->hasMany(TemplateOrderVtbAb::className(), ['company_department_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getWorkers()
    {
        return $this->hasMany(Worker::className(), ['company_department_id' => 'id']);
    }

    // ручное добавление
    public function getLastShiftDepartment()
    {
        return $this->hasMany(ShiftDepartment::className(), ['company_department_id' => 'id'])
            ->orderBy(['date_time'=>SORT_DESC])->select('plan_shift_id')->asArray(true)->limit(1)->all();
    }

    // ручное добавление
    public function getLastShiftDepartmentObject()
    {
        return $this->hasMany(ShiftDepartment::className(), ['company_department_id' => 'id'])
            ->orderBy(['date_time'=>SORT_DESC])->select('plan_shift_id')->one();
    }

    /**
     * Ручное добавление связи между связки департаментов и компаний к прараметрам департамента
     * @return \yii\db\ActiveQuery
     */
    public function getDepartmentParameterSummary()
    {
        return $this->hasMany(DepartmentParameterSummary::className(), ['company_department_id' => 'id']);
    }
    public function getDepartmentParameterSummaryWorkerSettings()
    {
        return $this->hasMany(DepartmentParameterSummaryWorkerSettings::className(), ['department_parameter_id' => 'id'])->via('departmentParameterSummary');
    }
    public function getDepartmentParameter()
    {
        return $this->hasMany(DepartmentParameter::className(), ['company_department_id' => 'id']);
    }
    public function getDepartmentParameterValue()
    {
        return $this->hasMany(DepartmentParameterValue::className(), ['department_parameter_id' => 'id'])->via('departmentParameter');
    }
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCompany1()
    {
        return $this->hasOne(Company::className(), ['id' => 'company_id'])->alias('company1');
    }
}
