<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "status".
 *
 * @property int $id ключ справочника статусов
 * @property string $title название статуса
 * @property string $trigger условие срабатывания статуса
 * @property int $status_type_id внешний ключ справочника статусов
 *
 * @property ConfigurationFace[] $configurationFaces
 * @property ConjunctionParameterHandbookValue[] $conjunctionParameterHandbookValues
 * @property ConjunctionParameterValue[] $conjunctionParameterValues
 * @property EdgeParameterHandbookValue[] $edgeParameterHandbookValues
 * @property EdgeParameterValue[] $edgeParameterValues
 * @property EdgeStatus[] $edgeStatuses
 * @property EnergyMineParameterHandbookValue[] $energyMineParameterHandbookValues
 * @property EnergyMineParameterValue[] $energyMineParameterValues
 * @property EquipmentParameterHandbookValue[] $equipmentParameterHandbookValues
 * @property EquipmentParameterValue[] $equipmentParameterValues
 * @property EventStatus[] $eventStatuses
 * @property FaceParameterHandbookValue[] $faceParameterHandbookValues
 * @property FaceParameterValue[] $faceParameterValues
 * @property GraficTabelStatus[] $graficTabelStatuses
 * @property GraphicList[] $graphicLists
 * @property GraphicStatus[] $graphicStatuses
 * @property MineParameterHandbookValue[] $mineParameterHandbookValues
 * @property MineParameterValue[] $mineParameterValues
 * @property MineSituationFact[] $mineSituationFacts
 * @property MineSituationFactParameter[] $mineSituationFactParameters
 * @property NominalConjunctionParameter[] $nominalConjunctionParameters
 * @property NominalEnergyMineParameter[] $nominalEnergyMineParameters
 * @property NominalEquipmentParameter[] $nominalEquipmentParameters
 * @property NominalFaceParameter[] $nominalFaceParameters
 * @property NominalOperationParameter[] $nominalOperationParameters
 * @property NominalOperationRegulationFactParameter[] $nominalOperationRegulationFactParameters
 * @property NominalOperationRegulationParameter[] $nominalOperationRegulationParameters
 * @property NominalPlaceParameter[] $nominalPlaceParameters
 * @property NominalPlastParameter[] $nominalPlastParameters
 * @property NominalPpsMineParameter[] $nominalPpsMineParameters
 * @property NominalTypeObjectParameter[] $nominalTypeObjectParameters
 * @property NominalWorkerParameter[] $nominalWorkerParameters
 * @property OperationByWorkerStatus[] $operationByWorkerStatuses
 * @property OperationParameterHandbookValue[] $operationParameterHandbookValues
 * @property OperationParameterValue[] $operationParameterValues
 * @property OperationRegulationFactParameterHandbookValue[] $operationRegulationFactParameterHandbookValues
 * @property OperationRegulationFactParameterValue[] $operationRegulationFactParameterValues
 * @property OperationRegulationParameterHandbookValue[] $operationRegulationParameterHandbookValues
 * @property OperationRegulationParameterValue[] $operationRegulationParameterValues
 * @property OrderByChaneGroupOperationStatus[] $orderByChaneGroupOperationStatuses
 * @property OrderByWorkerStatus[] $orderByWorkerStatuses
 * @property OrderRelationStatus[] $orderRelationStatuses
 * @property OrderStatus[] $orderStatuses
 * @property PassportSketch[] $passportSketches
 * @property Passport[] $passports
 * @property PlaFact[] $plaFacts
 * @property PlaceParameterHandbookValue[] $placeParameterHandbookValues
 * @property PlaceParameterValue[] $placeParameterValues
 * @property PlastParameterHandbookValue[] $plastParameterHandbookValues
 * @property PlastParameterValue[] $plastParameterValues
 * @property PpsMineParameterHandbookValue[] $ppsMineParameterHandbookValues
 * @property PpsMineParameterValue[] $ppsMineParameterValues
 * @property RegulationFact[] $regulationFacts
 * @property SensorParameterHandbookValue[] $sensorParameterHandbookValues
 * @property SensorParameterValue[] $sensorParameterValues
 * @property SensorParameterValueErrors[] $sensorParameterValueErrors
 * @property SituationFact[] $situationFacts
 * @property SituationFactParameterValue[] $situationFactParameterValues
 * @property StatusType $statusType
 * @property StrataGateway[] $strataGateways
 * @property StrataMobileDevice[] $strataMobileDevices
 * @property StrataNode[] $strataNodes
 * @property TextMessage[] $textMessages
 * @property TimetableStatus[] $timetableStatuses
 * @property TypeObjectParameterValue[] $typeObjectParameterValues
 * @property WorkerParameterHandbookValue[] $workerParameterHandbookValues
 * @property WorkerParameterValue[] $workerParameterValues
 */
class Status extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'status';
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
            [['title', 'trigger', 'status_type_id'], 'required'],
            [['status_type_id'], 'integer'],
            [['title', 'trigger'], 'string', 'max' => 255],
            [['title'], 'unique'],
            [['status_type_id'], 'exist', 'skipOnError' => true, 'targetClass' => StatusType::className(), 'targetAttribute' => ['status_type_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ключ справочника статусов',
            'title' => 'название статуса',
            'trigger' => 'условие срабатывания статуса',
            'status_type_id' => 'внешний ключ справочника статусов',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getConfigurationFaces()
    {
        return $this->hasMany(ConfigurationFace::className(), ['status_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getConjunctionParameterHandbookValues()
    {
        return $this->hasMany(ConjunctionParameterHandbookValue::className(), ['status_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getConjunctionParameterValues()
    {
        return $this->hasMany(ConjunctionParameterValue::className(), ['status_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getEdgeParameterHandbookValues()
    {
        return $this->hasMany(EdgeParameterHandbookValue::className(), ['status_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getEdgeParameterValues()
    {
        return $this->hasMany(EdgeParameterValue::className(), ['status_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getEdgeStatuses()
    {
        return $this->hasMany(EdgeStatus::className(), ['status_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getEnergyMineParameterHandbookValues()
    {
        return $this->hasMany(EnergyMineParameterHandbookValue::className(), ['status_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getEnergyMineParameterValues()
    {
        return $this->hasMany(EnergyMineParameterValue::className(), ['status_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getEquipmentParameterHandbookValues()
    {
        return $this->hasMany(EquipmentParameterHandbookValue::className(), ['status_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getEquipmentParameterValues()
    {
        return $this->hasMany(EquipmentParameterValue::className(), ['status_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getEventStatuses()
    {
        return $this->hasMany(EventStatus::className(), ['status_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getFaceParameterHandbookValues()
    {
        return $this->hasMany(FaceParameterHandbookValue::className(), ['status_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getFaceParameterValues()
    {
        return $this->hasMany(FaceParameterValue::className(), ['status_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getGraficTabelStatuses()
    {
        return $this->hasMany(GraficTabelStatus::className(), ['status_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getGraphicLists()
    {
        return $this->hasMany(GraphicList::className(), [' status_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getGraphicStatuses()
    {
        return $this->hasMany(GraphicStatus::className(), ['status_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMineParameterHandbookValues()
    {
        return $this->hasMany(MineParameterHandbookValue::className(), ['status_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMineParameterValues()
    {
        return $this->hasMany(MineParameterValue::className(), ['status_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMineSituationFacts()
    {
        return $this->hasMany(MineSituationFact::className(), ['status_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMineSituationFactParameters()
    {
        return $this->hasMany(MineSituationFactParameter::className(), ['status_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getNominalConjunctionParameters()
    {
        return $this->hasMany(NominalConjunctionParameter::className(), ['status_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getNominalEnergyMineParameters()
    {
        return $this->hasMany(NominalEnergyMineParameter::className(), ['status_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getNominalEquipmentParameters()
    {
        return $this->hasMany(NominalEquipmentParameter::className(), ['status_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getNominalFaceParameters()
    {
        return $this->hasMany(NominalFaceParameter::className(), ['status_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getNominalOperationParameters()
    {
        return $this->hasMany(NominalOperationParameter::className(), ['status_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getNominalOperationRegulationFactParameters()
    {
        return $this->hasMany(NominalOperationRegulationFactParameter::className(), ['status_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getNominalOperationRegulationParameters()
    {
        return $this->hasMany(NominalOperationRegulationParameter::className(), ['status_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getNominalPlaceParameters()
    {
        return $this->hasMany(NominalPlaceParameter::className(), ['status_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getNominalPlastParameters()
    {
        return $this->hasMany(NominalPlastParameter::className(), ['status_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getNominalPpsMineParameters()
    {
        return $this->hasMany(NominalPpsMineParameter::className(), ['status_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getNominalTypeObjectParameters()
    {
        return $this->hasMany(NominalTypeObjectParameter::className(), ['status_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getNominalWorkerParameters()
    {
        return $this->hasMany(NominalWorkerParameter::className(), ['status_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOperationByWorkerStatuses()
    {
        return $this->hasMany(OperationByWorkerStatus::className(), ['status_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOperationParameterHandbookValues()
    {
        return $this->hasMany(OperationParameterHandbookValue::className(), ['status_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOperationParameterValues()
    {
        return $this->hasMany(OperationParameterValue::className(), ['status_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOperationRegulationFactParameterHandbookValues()
    {
        return $this->hasMany(OperationRegulationFactParameterHandbookValue::className(), ['status_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOperationRegulationFactParameterValues()
    {
        return $this->hasMany(OperationRegulationFactParameterValue::className(), ['status_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOperationRegulationParameterHandbookValues()
    {
        return $this->hasMany(OperationRegulationParameterHandbookValue::className(), ['status_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOperationRegulationParameterValues()
    {
        return $this->hasMany(OperationRegulationParameterValue::className(), ['status_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOrderByChaneGroupOperationStatuses()
    {
        return $this->hasMany(OrderByChaneGroupOperationStatus::className(), ['status_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOrderByWorkerStatuses()
    {
        return $this->hasMany(OrderByWorkerStatus::className(), ['status_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOrderRelationStatuses()
    {
        return $this->hasMany(OrderRelationStatus::className(), ['status_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOrderStatuses()
    {
        return $this->hasMany(OrderStatus::className(), ['status_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPassportSketches()
    {
        return $this->hasMany(PassportSketch::className(), ['status_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPassports()
    {
        return $this->hasMany(Passport::className(), ['id' => 'passport_id'])->viaTable('passport_sketch', ['status_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPlaFacts()
    {
        return $this->hasMany(PlaFact::className(), ['status_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPlaceParameterHandbookValues()
    {
        return $this->hasMany(PlaceParameterHandbookValue::className(), ['status_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPlaceParameterValues()
    {
        return $this->hasMany(PlaceParameterValue::className(), ['status_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPlastParameterHandbookValues()
    {
        return $this->hasMany(PlastParameterHandbookValue::className(), ['status_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPlastParameterValues()
    {
        return $this->hasMany(PlastParameterValue::className(), ['status_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPpsMineParameterHandbookValues()
    {
        return $this->hasMany(PpsMineParameterHandbookValue::className(), ['status_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPpsMineParameterValues()
    {
        return $this->hasMany(PpsMineParameterValue::className(), ['status_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRegulationFacts()
    {
        return $this->hasMany(RegulationFact::className(), ['status_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSensorParameterHandbookValues()
    {
        return $this->hasMany(SensorParameterHandbookValue::className(), ['status_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSensorParameterValues()
    {
        return $this->hasMany(SensorParameterValue::className(), ['status_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSensorParameterValueErrors()
    {
        return $this->hasMany(SensorParameterValueErrors::className(), ['status_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSituationFacts()
    {
        return $this->hasMany(SituationFact::className(), ['status_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSituationFactParameterValues()
    {
        return $this->hasMany(SituationFactParameterValue::className(), ['status_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStatusType()
    {
        return $this->hasOne(StatusType::className(), ['id' => 'status_type_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStrataGateways()
    {
        return $this->hasMany(StrataGateway::className(), ['status_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStrataMobileDevices()
    {
        return $this->hasMany(StrataMobileDevice::className(), ['status_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStrataNodes()
    {
        return $this->hasMany(StrataNode::className(), ['status_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTextMessages()
    {
        return $this->hasMany(TextMessage::className(), ['status_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTimetableStatuses()
    {
        return $this->hasMany(TimetableStatus::className(), ['status_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTypeObjectParameterValues()
    {
        return $this->hasMany(TypeObjectParameterValue::className(), ['status_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getWorkerParameterHandbookValues()
    {
        return $this->hasMany(WorkerParameterHandbookValue::className(), ['status_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getWorkerParameterValues()
    {
        return $this->hasMany(WorkerParameterValue::className(), ['status_id' => 'id']);
    }

    public function getInjunctionStatuses()
    {
        return $this->hasMany(InjunctionStatus::className(), ['status_id' => 'id']);
    }
}
