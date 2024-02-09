<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "repair_map_specific_role".
 *
 * @property int $id
 * @property int $repair_map_specific_equipment_section_id Уникальный идентификатор секции оборудования из списка ТКР конкретных объектов 
 * @property int $role_id Уникальный идентификатор роли\\n
 * @property string $discharge Разряд
 * @property string $quantity Количество
 *
 * @property RepairMapSpecificEquipmentSection $repairMapSpecificEquipmentSection
 * @property Role $role
 */
class RepairMapSpecificRole extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'repair_map_specific_role';
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
            [['repair_map_specific_equipment_section_id', 'role_id', 'discharge', 'quantity'], 'required'],
            [['repair_map_specific_equipment_section_id', 'role_id'], 'integer'],
            [['discharge'], 'string', 'max' => 55],
            [['quantity'], 'string', 'max' => 20],
            [['repair_map_specific_equipment_section_id'], 'exist', 'skipOnError' => true, 'targetClass' => RepairMapSpecificEquipmentSection::className(), 'targetAttribute' => ['repair_map_specific_equipment_section_id' => 'id']],
            [['role_id'], 'exist', 'skipOnError' => true, 'targetClass' => Role::className(), 'targetAttribute' => ['role_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'repair_map_specific_equipment_section_id' => 'Уникальный идентификатор секции оборудования из списка ТКР конкретных объектов ',
            'role_id' => 'Уникальный идентификатор роли\\\\n',
            'discharge' => 'Разряд',
            'quantity' => 'Количество',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRepairMapSpecificEquipmentSection()
    {
        return $this->hasOne(RepairMapSpecificEquipmentSection::className(), ['id' => 'repair_map_specific_equipment_section_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRole()
    {
        return $this->hasOne(Role::className(), ['id' => 'role_id']);
    }
}
