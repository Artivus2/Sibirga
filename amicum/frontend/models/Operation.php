<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "operation".
 *
 * @property int $id ключ справочника операций
 * @property string $title Название операци
 * @property int $operation_type_id внешний ключ справоника типов операций
 * @property int $unit_id внешний ключ справочника единиц измерения
 * @property int $value объём операции на нагрузку по человекам и по времени 1 метр крепления горной выработки 2 человеками в течение 30минут
 * @property string|null $description Описание операции
 * @property float $operation_load_value Нагрузка по операции в человеках
 * @property string|null $short_title
 * @property float|null $opeartion_load_time нагрузка по операции во времени
 *
 * @property CorrectMeasures[] $correctMeasures
 * @property EventJournalCorrectMeasure[] $eventJournalCorrectMeasures
 * @property EventJournal[] $eventJournals
 * @property OperationType $operationType
 * @property Unit $unit
 * @property OperationEquipment[] $operationEquipments
 * @property OperationFunction[] $operationFunctions
 * @property OperationGroup[] $operationGroups
 * @property OperationParameters[] $operationParameters
 * @property OperationRegulation[] $operationRegulations
 * @property OperationRegulationFact[] $operationRegulationFacts
 * @property OrderOperation[] $orderOperations
 * @property OrderOperationPlaceVtbAb[] $orderOperationPlaceVtbAbs
 * @property OrderPlaceVtbAb[] $orderPlaceVtbAbs
 * @property OrderPermitOperation[] $orderPermitOperations
 * @property OrderTemplateOperation[] $orderTemplateOperations
 * @property PassportOperation[] $passportOperations
 * @property PassportOperation[] $passportOperations0
 * @property PlaceOperation[] $placeOperations
 * @property RepairMapSpecificOperation[] $repairMapSpecificOperations
 * @property RepairMapTypicalOperation[] $repairMapTypicalOperations
 * @property SituationJournalCorrectMeasure[] $situationJournalCorrectMeasures
 * @property SituationJournal[] $situationJournals
 * @property StopPb[] $stopPbs
 */
class Operation extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'operation';
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
            [['title', 'operation_type_id', 'unit_id', 'value', 'operation_load_value'], 'required'],
            [['operation_type_id', 'unit_id', 'value'], 'integer'],
            [['operation_load_value', 'opeartion_load_time'], 'number'],
            [['title', 'description'], 'string', 'max' => 255],
            [['short_title'], 'string', 'max' => 30],
            [['operation_type_id'], 'exist', 'skipOnError' => true, 'targetClass' => OperationType::className(), 'targetAttribute' => ['operation_type_id' => 'id']],
            [['unit_id'], 'exist', 'skipOnError' => true, 'targetClass' => Unit::className(), 'targetAttribute' => ['unit_id' => 'id']],
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
            'operation_type_id' => 'Operation Type ID',
            'unit_id' => 'Unit ID',
            'value' => 'Value',
            'description' => 'Description',
            'operation_load_value' => 'Operation Load Value',
            'short_title' => 'Short Title',
            'opeartion_load_time' => 'Opeartion Load Time',
        ];
    }

    /**
     * Gets query for [[CorrectMeasures]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCorrectMeasures()
    {
        return $this->hasMany(CorrectMeasures::className(), ['operation_id' => 'id']);
    }

    /**
     * Gets query for [[EventJournalCorrectMeasures]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getEventJournalCorrectMeasures()
    {
        return $this->hasMany(EventJournalCorrectMeasure::className(), ['operation_id' => 'id']);
    }

    /**
     * Gets query for [[EventJournals]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getEventJournals()
    {
        return $this->hasMany(EventJournal::className(), ['id' => 'event_journal_id'])->viaTable('event_journal_correct_measure', ['operation_id' => 'id']);
    }

    /**
     * Gets query for [[OperationType]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getOperationType()
    {
        return $this->hasOne(OperationType::className(), ['id' => 'operation_type_id']);
    }

    /**
     * Gets query for [[Unit]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getUnit()
    {
        return $this->hasOne(Unit::className(), ['id' => 'unit_id']);
    }

    /**
     * Gets query for [[OperationEquipments]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getOperationEquipments()
    {
        return $this->hasMany(OperationEquipment::className(), ['operation_id' => 'id']);
    }

    /**
     * Gets query for [[OperationFunctions]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getOperationFunctions()
    {
        return $this->hasMany(OperationFunction::className(), ['operation_id' => 'id']);
    }

    /**
     * Gets query for [[OperationGroups]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getOperationGroups()
    {
        return $this->hasMany(OperationGroup::className(), ['operation_id' => 'id']);
    }

    /**
     * Gets query for [[OperationParameters]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getOperationParameters()
    {
        return $this->hasMany(OperationParameters::className(), ['operation_id' => 'id']);
    }

    /**
     * Gets query for [[OperationRegulations]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getOperationRegulations()
    {
        return $this->hasMany(OperationRegulation::className(), ['operation_id' => 'id']);
    }

    /**
     * Gets query for [[OperationRegulationFacts]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getOperationRegulationFacts()
    {
        return $this->hasMany(OperationRegulationFact::className(), ['operation_id' => 'id']);
    }

    /**
     * Gets query for [[OrderOperations]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getOrderOperations()
    {
        return $this->hasMany(OrderOperation::className(), ['operation_id' => 'id']);
    }

    /**
     * Gets query for [[OrderOperationPlaceVtbAbs]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getOrderOperationPlaceVtbAbs()
    {
        return $this->hasMany(OrderOperationPlaceVtbAb::className(), ['operation_id' => 'id']);
    }

    /**
     * Gets query for [[OrderPlaceVtbAbs]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getOrderPlaceVtbAbs()
    {
        return $this->hasMany(OrderPlaceVtbAb::className(), ['id' => 'order_place_vtb_ab_id'])->viaTable('order_operation_place_vtb_ab', ['operation_id' => 'id']);
    }

    /**
     * Gets query for [[OrderPermitOperations]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getOrderPermitOperations()
    {
        return $this->hasMany(OrderPermitOperation::className(), ['operation_id' => 'id']);
    }

    /**
     * Gets query for [[OrderTemplateOperations]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getOrderTemplateOperations()
    {
        return $this->hasMany(OrderTemplateOperation::className(), ['operation_id' => 'id']);
    }

    /**
     * Gets query for [[PassportOperations]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getPassportOperations()
    {
        return $this->hasMany(PassportOperation::className(), ['operation_id' => 'id']);
    }

    /**
     * Gets query for [[PassportOperations0]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getPassportOperations0()
    {
        return $this->hasMany(PassportOperation::className(), ['operation_id' => 'id']);
    }

    /**
     * Gets query for [[PlaceOperations]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getPlaceOperations()
    {
        return $this->hasMany(PlaceOperation::className(), ['operation_id' => 'id']);
    }

    /**
     * Gets query for [[RepairMapSpecificOperations]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getRepairMapSpecificOperations()
    {
        return $this->hasMany(RepairMapSpecificOperation::className(), ['operation_id' => 'id']);
    }

    /**
     * Gets query for [[RepairMapTypicalOperations]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getRepairMapTypicalOperations()
    {
        return $this->hasMany(RepairMapTypicalOperation::className(), ['operation_id' => 'id']);
    }

    /**
     * Gets query for [[SituationJournalCorrectMeasures]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getSituationJournalCorrectMeasures()
    {
        return $this->hasMany(SituationJournalCorrectMeasure::className(), ['operation_id' => 'id']);
    }

    /**
     * Gets query for [[SituationJournals]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getSituationJournals()
    {
        return $this->hasMany(SituationJournal::className(), ['id' => 'situation_journal_id'])->viaTable('situation_journal_correct_measure', ['operation_id' => 'id']);
    }

    /**
     * Gets query for [[StopPbs]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getStopPbs()
    {
        return $this->hasMany(StopPb::className(), ['operation_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOrderPlaces()
    {
        return $this->hasMany(OrderPlace::className(), ['id' => 'order_place_id'])->viaTable('order_operation', ['operation_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOrderTemplatePlaces()
    {
        return $this->hasMany(OrderTemplatePlace::className(), ['id' => 'order_template_place_id'])->viaTable('order_template_operation', ['operation_id' => 'id']);
    }
}
