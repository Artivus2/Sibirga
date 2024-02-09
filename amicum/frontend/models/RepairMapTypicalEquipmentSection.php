<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "repair_map_typical_equipment_section".
 *
 * @property int $id Идентификатор самой таблицы (автоинкрементный)
 * @property int $repair_map_typical_id Уникальный идентификатор ТКР типовых объектов
 * @property int $equipment_section_id Уникальный идентификатор секции оборудования
 *
 * @property RepairMapTypicalDevice[] $repairMapTypicalDevices
 * @property EquipmentSection $equipmentSection
 * @property RepairMapTypical $repairMapTypical
 * @property RepairMapTypicalInstrument[] $repairMapTypicalInstruments
 * @property RepairMapTypicalMaterial[] $repairMapTypicalMaterials
 * @property RepairMapTypicalOperation[] $repairMapTypicalOperations
 * @property RepairMapTypicalRole[] $repairMapTypicalRoles
 */
class RepairMapTypicalEquipmentSection extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'repair_map_typical_equipment_section';
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
            [['repair_map_typical_id', 'equipment_section_id'], 'required'],
            [['repair_map_typical_id', 'equipment_section_id'], 'integer'],
            [['equipment_section_id'], 'exist', 'skipOnError' => true, 'targetClass' => EquipmentSection::className(), 'targetAttribute' => ['equipment_section_id' => 'id']],
            [['repair_map_typical_id'], 'exist', 'skipOnError' => true, 'targetClass' => RepairMapTypical::className(), 'targetAttribute' => ['repair_map_typical_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'Идентификатор самой таблицы (автоинкрементный)',
            'repair_map_typical_id' => 'Уникальный идентификатор ТКР типовых объектов',
            'equipment_section_id' => 'Уникальный идентификатор секции оборудования',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRepairMapTypicalDevices()
    {
        return $this->hasMany(RepairMapTypicalDevice::className(), ['repair_map_typical_equipment_section_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getEquipmentSection()
    {
        return $this->hasOne(EquipmentSection::className(), ['id' => 'equipment_section_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRepairMapTypical()
    {
        return $this->hasOne(RepairMapTypical::className(), ['id' => 'repair_map_typical_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRepairMapTypicalInstruments()
    {
        return $this->hasMany(RepairMapTypicalInstrument::className(), ['repair_map_typical_equipment_section_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRepairMapTypicalMaterials()
    {
        return $this->hasMany(RepairMapTypicalMaterial::className(), ['repair_map_typical_equipment_section_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRepairMapTypicalOperations()
    {
        return $this->hasMany(RepairMapTypicalOperation::className(), ['repair_map_typical_equipment_section_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRepairMapTypicalRoles()
    {
        return $this->hasMany(RepairMapTypicalRole::className(), ['repair_map_typical_equipment_section_id' => 'id']);
    }
}
