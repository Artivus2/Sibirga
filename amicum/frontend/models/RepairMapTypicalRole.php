<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "repair_map_typical_role".
 *
 * @property int $id Идентификатор самой таблицы (автоинкрементный)
 * @property int $repair_map_typical_equipment_section_id Уникальный идентификатор технологической карты ремонтов типовых объектов  
 * @property int $role_id Уникальный идентификатор роли
 * @property string $quantity Количество 
 * @property string $discharge Разряд
 *
 * @property RepairMapTypicalEquipmentSection $repairMapTypicalEquipmentSection
 * @property Role $role
 */
class RepairMapTypicalRole extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'repair_map_typical_role';
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
            [['repair_map_typical_equipment_section_id', 'role_id', 'quantity', 'discharge'], 'required'],
            [['repair_map_typical_equipment_section_id', 'role_id'], 'integer'],
            [['quantity'], 'string', 'max' => 55],
            [['discharge'], 'string', 'max' => 20],
            [['repair_map_typical_equipment_section_id'], 'exist', 'skipOnError' => true, 'targetClass' => RepairMapTypicalEquipmentSection::className(), 'targetAttribute' => ['repair_map_typical_equipment_section_id' => 'id']],
            [['role_id'], 'exist', 'skipOnError' => true, 'targetClass' => Role::className(), 'targetAttribute' => ['role_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'Идентификатор самой таблицы (автоинкрементный)',
            'repair_map_typical_equipment_section_id' => 'Уникальный идентификатор технологической карты ремонтов типовых объектов  ',
            'role_id' => 'Уникальный идентификатор роли',
            'quantity' => 'Количество ',
            'discharge' => 'Разряд',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRepairMapTypicalEquipmentSection()
    {
        return $this->hasOne(RepairMapTypicalEquipmentSection::className(), ['id' => 'repair_map_typical_equipment_section_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRole()
    {
        return $this->hasOne(Role::className(), ['id' => 'role_id']);
    }
}
