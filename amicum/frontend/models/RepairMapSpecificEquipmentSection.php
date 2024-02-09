<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "repair_map_specific_equipment_section".
 *
 * @property int $id Идентификатор самой таблицы (автоинкрементный)
 * @property int $repair_map_specific_id Уникальный идентификатор ТКР конкретных объектов
 * @property int $equipment_section_id Уникальный идентификатор секции оборудования
 *
 * @property RepairMapSpecificDevice[] $repairMapSpecificDevices
 * @property EquipmentSection $equipmentSection
 * @property RepairMapSpecific $repairMapSpecific
 * @property RepairMapSpecificInstrument[] $repairMapSpecificInstruments
 * @property RepairMapSpecificMaterial[] $repairMapSpecificMaterials
 * @property RepairMapSpecificOperation[] $repairMapSpecificOperations
 * @property RepairMapSpecificRole[] $repairMapSpecificRoles
 */
class RepairMapSpecificEquipmentSection extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'repair_map_specific_equipment_section';
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
            [['repair_map_specific_id', 'equipment_section_id'], 'required'],
            [['repair_map_specific_id', 'equipment_section_id'], 'integer'],
            [['equipment_section_id'], 'exist', 'skipOnError' => true, 'targetClass' => EquipmentSection::className(), 'targetAttribute' => ['equipment_section_id' => 'id']],
            [['repair_map_specific_id'], 'exist', 'skipOnError' => true, 'targetClass' => RepairMapSpecific::className(), 'targetAttribute' => ['repair_map_specific_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'Идентификатор самой таблицы (автоинкрементный)',
            'repair_map_specific_id' => 'Уникальный идентификатор ТКР конкретных объектов',
            'equipment_section_id' => 'Уникальный идентификатор секции оборудования',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRepairMapSpecificDevices()
    {
        return $this->hasMany(RepairMapSpecificDevice::className(), ['repair_map_specific_equipment_section_id' => 'id']);
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
    public function getRepairMapSpecific()
    {
        return $this->hasOne(RepairMapSpecific::className(), ['id' => 'repair_map_specific_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRepairMapSpecificInstruments()
    {
        return $this->hasMany(RepairMapSpecificInstrument::className(), ['repair_map_specific_equipment_section_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRepairMapSpecificMaterials()
    {
        return $this->hasMany(RepairMapSpecificMaterial::className(), ['repair_map_specific_equipment_section_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRepairMapSpecificOperations()
    {
        return $this->hasMany(RepairMapSpecificOperation::className(), ['repair_map_specific_equipment_section_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRepairMapSpecificRoles()
    {
        return $this->hasMany(RepairMapSpecificRole::className(), ['repair_map_specific_equipment_section_id' => 'id']);
    }
}
