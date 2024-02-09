<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "repair_map_typical_material".
 *
 * @property int $id Идентификатор  самой таблицы (автоинкрементный)
 * @property int $repair_map_typical_equipment_section_id Уникальный идентификатор секции оборудования из списка ТКР типовых объектов \\n
 * @property int $material_id Уникальный идентификатор материала
 * @property string $quantity Количество материала
 *
 * @property Material $material
 * @property RepairMapTypicalEquipmentSection $repairMapTypicalEquipmentSection
 */
class RepairMapTypicalMaterial extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'repair_map_typical_material';
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
            [['repair_map_typical_equipment_section_id', 'material_id'], 'required'],
            [['repair_map_typical_equipment_section_id', 'material_id'], 'integer'],
            [['quantity'], 'string', 'max' => 55],
            [['material_id'], 'exist', 'skipOnError' => true, 'targetClass' => Material::className(), 'targetAttribute' => ['material_id' => 'id']],
            [['repair_map_typical_equipment_section_id'], 'exist', 'skipOnError' => true, 'targetClass' => RepairMapTypicalEquipmentSection::className(), 'targetAttribute' => ['repair_map_typical_equipment_section_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'Идентификатор  самой таблицы (автоинкрементный)',
            'repair_map_typical_equipment_section_id' => 'Уникальный идентификатор секции оборудования из списка ТКР типовых объектов \\\\n',
            'material_id' => 'Уникальный идентификатор материала',
            'quantity' => 'Количество материала',
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
    public function getRepairMapTypicalEquipmentSection()
    {
        return $this->hasOne(RepairMapTypicalEquipmentSection::className(), ['id' => 'repair_map_typical_equipment_section_id']);
    }
}
