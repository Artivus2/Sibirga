<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "repair_map_specific_material".
 *
 * @property int $id Идентификатор  самой таблицы (автоинкрементный)\\n
 * @property int $repair_map_specific_equipment_section_id Уникальный идентификатор секции оборудования из списка ТКР конкретных объектов (конкретных оборудований)\\n
 * @property int $material_id Уникальный идентификатор материала(запчасти)\\n
 * @property string $quantity Количество материала 
 *
 * @property Material $material
 * @property RepairMapSpecificEquipmentSection $repairMapSpecificEquipmentSection
 */
class RepairMapSpecificMaterial extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'repair_map_specific_material';
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
            [['repair_map_specific_equipment_section_id', 'material_id', 'quantity'], 'required'],
            [['repair_map_specific_equipment_section_id', 'material_id'], 'integer'],
            [['quantity'], 'string', 'max' => 55],
            [['material_id'], 'exist', 'skipOnError' => true, 'targetClass' => Material::className(), 'targetAttribute' => ['material_id' => 'id']],
            [['repair_map_specific_equipment_section_id'], 'exist', 'skipOnError' => true, 'targetClass' => RepairMapSpecificEquipmentSection::className(), 'targetAttribute' => ['repair_map_specific_equipment_section_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'Идентификатор  самой таблицы (автоинкрементный)\\\\n',
            'repair_map_specific_equipment_section_id' => 'Уникальный идентификатор секции оборудования из списка ТКР конкретных объектов (конкретных оборудований)\\\\n',
            'material_id' => 'Уникальный идентификатор материала(запчасти)\\\\n',
            'quantity' => 'Количество материала ',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMaterial()
    {
        return $this->hasOne(Material::className(), ['id' => 'material_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRepairMapSpecificEquipmentSection()
    {
        return $this->hasOne(RepairMapSpecificEquipmentSection::className(), ['id' => 'repair_map_specific_equipment_section_id']);
    }
}
