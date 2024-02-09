<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "repair_map_specific_operation".
 *
 * @property int $id Идентификатор самой таблицы (автоинкрементный)
 * @property int $repair_map_specific_equipment_section_id Уникальный идентификатор секции оборудования из списка ТКР конкретных объектов \\n
 * @property int $operation_id Уникальный идентификатор наряда
 * @property string $quantity Количество человека часов (единица измерения)
 *
 * @property Operation $operation
 * @property RepairMapSpecificEquipmentSection $repairMapSpecificEquipmentSection
 */
class RepairMapSpecificOperation extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'repair_map_specific_operation';
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
            [['repair_map_specific_equipment_section_id', 'operation_id', 'quantity'], 'required'],
            [['repair_map_specific_equipment_section_id', 'operation_id'], 'integer'],
            [['quantity'], 'string', 'max' => 55],
            [['operation_id'], 'exist', 'skipOnError' => true, 'targetClass' => Operation::className(), 'targetAttribute' => ['operation_id' => 'id']],
            [['repair_map_specific_equipment_section_id'], 'exist', 'skipOnError' => true, 'targetClass' => RepairMapSpecificEquipmentSection::className(), 'targetAttribute' => ['repair_map_specific_equipment_section_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'Идентификатор самой таблицы (автоинкрементный)',
            'repair_map_specific_equipment_section_id' => 'Уникальный идентификатор секции оборудования из списка ТКР конкретных объектов \\\\n',
            'operation_id' => 'Уникальный идентификатор наряда',
            'quantity' => 'Количество человека часов (единица измерения)',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOperation()
    {
        return $this->hasOne(Operation::className(), ['id' => 'operation_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRepairMapSpecificEquipmentSection()
    {
        return $this->hasOne(RepairMapSpecificEquipmentSection::className(), ['id' => 'repair_map_specific_equipment_section_id']);
    }
}
