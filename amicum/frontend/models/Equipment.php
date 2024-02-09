<?php
/*
 * Copyright (c) 2020. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 *
 */

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "equipment".
 *
 * @property int $id ключ оборудования
 * @property string $title название оборудования
 * @property string|null $inventory_number инвентарный номер
 * @property int $object_id ключ типового объекта
 * @property int|null $parent_equipment_id родительское оборудование
 * @property string|null $date_time_sync Дата и время синхронизации позиции оборудования
 * @property int|null $sap_id ключ оборудования sap
 *
 * @property ActionOperationEquipment[] $actionOperationEquipments
 * @property Cyclegramm[] $cyclegramms
 * @property TypicalObject $object
 * @property EquipmentFunction[] $equipmentFunctions
 * @property EquipmentParameter[] $equipmentParameters
 * @property EquipmentSection[] $equipmentSections
 * @property Section[] $sections
 * @property EquipmentUnion[] $equipmentUnions
 * @property UnionEquipment[] $unionEquipments
 * @property ExpertiseEquipment[] $expertiseEquipments
 * @property Expertise[] $expertises
 * @property GraphicRepair[] $graphicRepairs
 * @property OperationEquipment[] $operationEquipments
 * @property OrderOperation[] $orderOperations
 * @property OrderPermitOperation[] $orderPermitOperations
 * @property Planogramma[] $planogrammas
 * @property RepairMapSpecific[] $repairMapSpecifics
 * @property StopPbEquipment[] $stopPbEquipments
 */
class Equipment extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'equipment';
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
            [['id', 'title', 'object_id'], 'required'],
            [['id', 'object_id', 'parent_equipment_id', 'sap_id'], 'integer'],
            [['date_time_sync'], 'safe'],
            [['title'], 'string', 'max' => 255],
            [['inventory_number'], 'string', 'max' => 20],
            [['id'], 'unique'],
            [['object_id'], 'exist', 'skipOnError' => true, 'targetClass' => TypicalObject::className(), 'targetAttribute' => ['object_id' => 'id']],
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
            'inventory_number' => 'Inventory Number',
            'object_id' => 'TypicalObject ID',
            'parent_equipment_id' => 'Parent Equipment ID',
            'date_time_sync' => 'Date Time Sync',
            'sap_id' => 'Sap ID',
        ];
    }

    /**
     * Gets query for [[ActionOperationEquipments]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getActionOperationEquipments()
    {
        return $this->hasMany(ActionOperationEquipment::className(), ['equipment_id' => 'id']);
    }

    /**
     * Gets query for [[Cyclegramms]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCyclegramms()
    {
        return $this->hasMany(Cyclegramm::className(), ['equipment_id' => 'id']);
    }

    /**
     * Gets query for [[TypicalObject]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getObject()
    {
        return $this->hasOne(TypicalObject::className(), ['id' => 'object_id']);
    }

    /**
     * Gets query for [[EquipmentFunctions]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getEquipmentFunctions()
    {
        return $this->hasMany(EquipmentFunction::className(), ['equipment_id' => 'id']);
    }

    /**
     * Gets query for [[EquipmentParameters]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getEquipmentParameters()
    {
        return $this->hasMany(EquipmentParameter::className(), ['equipment_id' => 'id']);
    }

    /**
     * Gets query for [[EquipmentSections]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getEquipmentSections()
    {
        return $this->hasMany(EquipmentSection::className(), ['equipment_id' => 'id']);
    }

    /**
     * Gets query for [[Sections]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getSections()
    {
        return $this->hasMany(Section::className(), ['id' => 'section_id'])->viaTable('equipment_section', ['equipment_id' => 'id']);
    }

    /**
     * Gets query for [[EquipmentUnions]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getEquipmentUnions()
    {
        return $this->hasMany(EquipmentUnion::className(), ['equipment_id' => 'id']);
    }

    /**
     * Gets query for [[UnionEquipments]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getUnionEquipments()
    {
        return $this->hasMany(UnionEquipment::className(), ['id' => 'union_equipment_id'])->viaTable('equipment_union', ['equipment_id' => 'id']);
    }

    /**
     * Gets query for [[ExpertiseEquipments]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getExpertiseEquipments()
    {
        return $this->hasMany(ExpertiseEquipment::className(), ['equipment_id' => 'id']);
    }

    /**
     * Gets query for [[Expertises]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getExpertises()
    {
        return $this->hasMany(Expertise::className(), ['id' => 'expertise_id'])->viaTable('expertise_equipment', ['equipment_id' => 'id']);
    }

    /**
     * Gets query for [[GraphicRepairs]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getGraphicRepairs()
    {
        return $this->hasMany(GraphicRepair::className(), ['equipment_id' => 'id']);
    }

    /**
     * Gets query for [[OperationEquipments]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getOperationEquipments()
    {
        return $this->hasMany(OperationEquipment::className(), ['equipment_id' => 'id']);
    }

    /**
     * Gets query for [[OrderOperations]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getOrderOperations()
    {
        return $this->hasMany(OrderOperation::className(), ['equipment_id' => 'id']);
    }

    /**
     * Gets query for [[OrderPermitOperations]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getOrderPermitOperations()
    {
        return $this->hasMany(OrderPermitOperation::className(), ['equipment_id' => 'id']);
    }

    /**
     * Gets query for [[Planogrammas]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getPlanogrammas()
    {
        return $this->hasMany(Planogramma::className(), ['equipment_id' => 'id']);
    }

    /**
     * Gets query for [[RepairMapSpecifics]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getRepairMapSpecifics()
    {
        return $this->hasMany(RepairMapSpecific::className(), ['equipment_id' => 'id']);
    }

    /**
     * Gets query for [[StopPbEquipments]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getStopPbEquipments()
    {
        return $this->hasMany(StopPbEquipment::className(), ['equipment_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOrderByChaneEquipments()
    {
        return $this->hasMany(OrderByChaneEquipment::className(), ['equipment_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOrderByChaneOperationEquipments()
    {
        return $this->hasMany(OrderByChaneOperationEquipment::className(), ['equipment_id' => 'id']);
    }

    /**
     * Gets query for [[EquipmentUnity]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getEquipmentUnity()
    {
        return $this->hasOne(EquipmentUnity::className(), ['equipment_id' => 'id']);
    }

    /**
     * Gets query for [[EquipmentAttachments]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getEquipmentAttachments()
    {
        return $this->hasMany(EquipmentAttachment::className(), ['equipment_id' => 'id']);
    }
}
