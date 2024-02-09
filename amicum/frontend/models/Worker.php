<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "worker".
 *
 * @property int $id
 * @property int $employee_id внешний ключ к таблице сотрудников
 * @property int $position_id внешний ключ к таблице должностей
 * @property int $company_department_id внешний ключ к таблице привязки подразделений и предприятий
 * @property string $tabel_number табельный номер
 * @property string $date_start дата начала трудоустройства
 * @property string $date_end дата окончания трудоустройства
 * @property int $mine_id шахта (участок горных работ) за которыми закреплен работник)
 * @property int $vgk Является ли сотрудник членом ВГК(1 - является, NULL не является)
 * @property string date_time_sync
 * @property string link_1c
 *
 * @property Briefer[] $briefers
 * @property Brigade[] $brigades
 * @property BrigadeWorker[] $brigadeWorkers
 * @property Chane[] $chanes
 * @property ChaneFact[] $chaneFacts
 * @property ChaneWorker[] $chaneWorkers
 * @property CheckingPlan[] $checkingPlans
 * @property CheckingWorkerType[] $checkingWorkerTypes
 * @property CompanyDepartmentWorkerVgk[] $companyDepartmentWorkerVgks
 * @property CorrectMeasures[] $correctMeasures
 * @property ExpertiseEquipment[] $expertiseEquipments
 * @property FactTabelWorker[] $factTabelWorkers
 * @property ForbiddenZapret[] $forbiddenZaprets
 * @property GraficTabelDateFact[] $graficTabelDateFacts
 * @property GraficTabelDatePlan[] $graficTabelDatePlans
 * @property GraphicList[] $graphicLists
 * @property GraphicRepair[] $graphicRepairs
 * @property GraphicStatus[] $graphicStatuses
 * @property GroupDepConfig[] $groupDepConfigs
 * @property GroupDepWorker[] $groupDepWorkers
 * @property GroupDepConfig[] $groupDepartmentConfigurations
 * @property Injunction[] $injunctions
 * @property InjunctionStatus[] $injunctionStatuses
 * @property Instructor[] $instructors
 * @property MedReport[] $medReports
 * @property OperationWorker[] $operationWorkers
 * @property OrderOperationWorkerStatus[] $orderOperationWorkerStatuses
 * @property OrderRelationStatus[] $orderRelationStatuses
 * @property OrderStatus[] $orderStatuses
 * @property OrderWorkerVgk[] $orderWorkerVgks
 * @property Physical[] $physicals
 * @property PhysicalWorker[] $physicalWorkers
 * @property PodgroupDep[] $podgroupDeps
 * @property PodgroupDepWorker[] $podgroupDepWorkers
 * @property Reason[] $reasons
 * @property ShiftWorker[] $shiftWorkers
 * @property StopFace[] $stopFaces
 * @property StopFace[] $stopFaces0
 * @property StopPbStatus[] $stopPbStatuses
 * @property TabelWorker[] $tabelWorkers
 * @property TextMessage[] $textMessages
 * @property TextMessage[] $textMessages0
 * @property User[] $users
 * @property Violator[] $violators
 * @property CompanyDepartment $companyDepartment
 * @property Employee $employee
 * @property Mine $mine
 * @property Position $position
 * @property WorkerObject[] $workerObjects
 * @property Object[] $objects
 * @property WorkerSiz[] $workerSizs
 */
class Worker extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'worker';
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
            [['employee_id', 'position_id', 'company_department_id', 'tabel_number', 'date_start'], 'required'],
            [['employee_id', 'position_id', 'company_department_id', 'mine_id', 'vgk'], 'integer'],
            [['date_start', 'date_end', 'date_time_sync'], 'safe'],
            [['link_1c'], 'string', 'max' => 100],
            [['tabel_number'], 'string', 'max' => 20],
            [['employee_id', 'position_id', 'company_department_id', 'date_start'], 'unique', 'targetAttribute' => ['employee_id', 'position_id', 'company_department_id', 'date_start']],
            [['position_id'], 'exist', 'skipOnError' => true, 'targetClass' => Position::className(), 'targetAttribute' => ['position_id' => 'id']],
            [['company_department_id'], 'exist', 'skipOnError' => true, 'targetClass' => CompanyDepartment::className(), 'targetAttribute' => ['company_department_id' => 'id']],
            [['employee_id'], 'exist', 'skipOnError' => true, 'targetClass' => Employee::className(), 'targetAttribute' => ['employee_id' => 'id']],
            [['mine_id'], 'exist', 'skipOnError' => true, 'targetClass' => Mine::className(), 'targetAttribute' => ['mine_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'employee_id' => 'внешний ключ к таблице сотрудников',
            'position_id' => 'внешний ключ к таблице должностей',
            'company_department_id' => 'внешний ключ к таблице привязки подразделений и предприятий',
            'tabel_number' => 'табельный номер',
            'date_start' => 'дата начала трудоустройства',
            'date_end' => 'дата окончания трудоустройства',
            'mine_id' => 'шахта (участок горных работ) за которыми закреплен работник)',
            'vgk' => 'Является ли сотрудник членом ВГК(1 - является, NULL не является)',
            'link_1c' => 'link_1c',
            'date_time_sync' => 'date_time_sync',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getBriefers()
    {
        return $this->hasMany(Briefer::className(), ['worker_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getBrigades()
    {
        return $this->hasMany(Brigade::className(), ['brigader_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getBrigadeWorkers()
    {
        return $this->hasMany(BrigadeWorker::className(), ['worker_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getChanes()
    {
        return $this->hasMany(Chane::className(), ['chaner_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getChaneFacts()
    {
        return $this->hasMany(ChaneFact::className(), ['worker_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getChaneWorkers()
    {
        return $this->hasMany(ChaneWorker::className(), ['worker_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCheckingPlans()
    {
        return $this->hasMany(CheckingPlan::className(), ['worker_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCheckingWorkerTypes()
    {
        return $this->hasMany(CheckingWorkerType::className(), ['worker_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCompanyDepartmentWorkerVgks()
    {
        return $this->hasMany(CompanyDepartmentWorkerVgk::className(), ['worker_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCorrectMeasures()
    {
        return $this->hasMany(CorrectMeasures::className(), ['worker_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getExpertiseEquipments()
    {
        return $this->hasMany(ExpertiseEquipment::className(), ['worker_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getFactTabelWorkers()
    {
        return $this->hasMany(FactTabelWorker::className(), ['worker_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getForbiddenZaprets()
    {
        return $this->hasMany(ForbiddenZapret::className(), ['worker_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getGraficTabelDateFacts()
    {
        return $this->hasMany(GraficTabelDateFact::className(), ['worker_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getGraficTabelDatePlans()
    {
        return $this->hasMany(GraficTabelDatePlan::className(), ['worker_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getGraphicLists()
    {
        return $this->hasMany(GraphicList::className(), ['worker_created_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getGraphicRepairs()
    {
        return $this->hasMany(GraphicRepair::className(), ['worker_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getGraphicStatuses()
    {
        return $this->hasMany(GraphicStatus::className(), ['worker_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getGroupDepConfigs()
    {
        return $this->hasMany(GroupDepConfig::className(), ['brigader_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getGroupDepWorkers()
    {
        return $this->hasMany(GroupDepWorker::className(), ['worker_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getGroupDepartmentConfigurations()
    {
        return $this->hasMany(GroupDepConfig::className(), ['id' => 'group_department_configuration_id'])->viaTable('group_dep_worker', ['worker_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getInjunctions()
    {
        return $this->hasMany(Injunction::className(), ['worker_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getInjunctionStatuses()
    {
        return $this->hasMany(InjunctionStatus::className(), ['worker_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getInstructors()
    {
        return $this->hasMany(Instructor::className(), ['worker_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMedReports()
    {
        return $this->hasMany(MedReport::className(), ['worker_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOperationWorkers()
    {
        return $this->hasMany(OperationWorker::className(), ['worker_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOrderOperationWorkerStatuses()
    {
        return $this->hasMany(OrderOperationWorkerStatus::className(), ['worker_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOrderRelationStatuses()
    {
        return $this->hasMany(OrderRelationStatus::className(), ['worker_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOrderStatuses()
    {
        return $this->hasMany(OrderStatus::className(), ['worker_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOrderWorkerVgks()
    {
        return $this->hasMany(OrderWorkerVgk::className(), ['worker_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPlaceCompanyDepartments()
    {
        return $this->hasMany(PlaceCompanyDepartment::className(), ['company_department_id' => 'company_department_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPhysicals()
    {
        return $this->hasMany(Physical::className(), ['worker_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPhysicalWorkers()
    {
        return $this->hasMany(PhysicalWorker::className(), ['worker_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPodgroupDeps()
    {
        return $this->hasMany(PodgroupDep::className(), ['chaner_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPodgroupDepWorkers()
    {
        return $this->hasMany(PodgroupDepWorker::className(), ['worker_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getReasons()
    {
        return $this->hasMany(Reason::className(), ['worker_guilty_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getShiftWorkers()
    {
        return $this->hasMany(ShiftWorker::className(), ['worker_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStopFaces()
    {
        return $this->hasMany(StopFace::className(), ['performer_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStopFaces0()
    {
        return $this->hasMany(StopFace::className(), ['dispatcher_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStopPbStatuses()
    {
        return $this->hasMany(StopPbStatus::className(), ['worker_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTabelWorkers()
    {
        return $this->hasMany(TabelWorker::className(), ['worker_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTextMessages()
    {
        return $this->hasMany(TextMessage::className(), ['reciever_worker_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTextMessages0()
    {
        return $this->hasMany(TextMessage::className(), ['sender_worker_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUsers()
    {
        return $this->hasMany(User::className(), ['worker_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getViolators()
    {
        return $this->hasMany(Violator::className(), ['worker_id' => 'id']);
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
    public function getEmployee()
    {
        return $this->hasOne(Employee::className(), ['id' => 'employee_id']);
    }
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getEmployee1()
    {
        return $this->hasOne(Employee::className(), ['id' => 'employee_id'])->alias('employee1');
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMine()
    {
        return $this->hasOne(Mine::className(), ['id' => 'mine_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPosition()
    {
        return $this->hasOne(Position::className(), ['id' => 'position_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPosition1()
    {
        return $this->hasOne(Position::className(), ['id' => 'position_id'])->alias('position1');
    }


    /**
     * @return \yii\db\ActiveQuery
     */
    public function getWorkerObjects()
    {
        return $this->hasMany(WorkerObject::className(), ['worker_id' => 'id']);
    }


    public function getObjects()
    {
        return $this->hasMany(TypicalObject::className(), ['id' => 'object_id'])->viaTable('worker_object', ['worker_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getWorkerSizs()
    {
        return $this->hasMany(WorkerSiz::className(), ['worker_id' => 'id']);
    }
    public function getLastShiftWorker()
    {
        return $this->hasMany(ShiftWorker::className(), ['worker_id' => 'id'])->orderBy(['date_time'=>SORT_DESC])
            ->select('plan_shift_id')->asArray(true)->limit(1)->all();
    }

    public function getDepartment()
    {
        return $this->hasOne(Department::className(), ['id' => 'department_id'])->via('companyDepartment');
    }

    public function getCompany()
    {
        return $this->hasOne(Company::className(), ['id' => 'company_id'])->via('companyDepartment');
    }

    /** Добавленные вручную связи */
    public function getChane()
    {
        return $this->hasMany(Chane::className(), ['id' => 'chane_id'])->via('chaneWorkers');
    }

    /** Добавленная вручную запись на получение бригады */
    public function getBrigade()
    {
        return $this->hasMany(Brigade::className(), ['id' => 'brigade_id'])->via('brigadeWorkers');
    }

    const ACTUAL_STATUS = 1;                                                                                            // Актуальный статус
    public function getActualBrigade()
    {
        return $this->hasOne(Brigade::className(), ['id' => 'brigade_id'])->onCondition(['brigade.status_id' => self::ACTUAL_STATUS])->via('brigadeWorkers');
    }

    public function getActualChane()
    {
        return $this->hasMany(Chane::className(), ['brigade_id' => 'id'])->via('actualBrigade')->via('chaneWorkers');
    }

    public function getWorkerObjectsRole()
    {
        return $this->hasMany(WorkerObjectRole::className(),['worker_object_id'=>'id'])->via('workerObjects');
    }

    public function getRole()
    {
        return $this->hasMany(Role::className(),['id'=>'role_id'])->via('workerObjects');
    }


    public function getChaneType()
    {
        return $this->hasMany(ChaneType::className(), ['id' => 'chane_type_id'])->via('chane');
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCompanyDepartment1()
    {
        return $this->hasOne(CompanyDepartment::className(), ['id' => 'company_department_id'])->alias('company_department1');
    }
}
