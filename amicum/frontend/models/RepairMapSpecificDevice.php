<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "repair_map_specific_device".
 *
 * @property int $id Идентификатор  самой таблицы (автоинкрементный)\\n
 * @property int $repair_map_specific_equipment_section_id Уникальный идентификатор секции оборудования из списка ТКР конкретных объектов 
 * @property int $device_id Уникальный идентификатор прибора
 * @property string $quantity Количество прибора
 *
 * @property Device $device
 * @property RepairMapSpecificEquipmentSection $repairMapSpecificEquipmentSection
 */
class RepairMapSpecificDevice extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'repair_map_specific_device';
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
            [['repair_map_specific_equipment_section_id', 'device_id', 'quantity'], 'required'],
            [['repair_map_specific_equipment_section_id', 'device_id'], 'integer'],
            [['quantity'], 'string', 'max' => 55],
            [['device_id'], 'exist', 'skipOnError' => true, 'targetClass' => Device::className(), 'targetAttribute' => ['device_id' => 'id']],
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
            'repair_map_specific_equipment_section_id' => 'Уникальный идентификатор секции оборудования из списка ТКР конкретных объектов ',
            'device_id' => 'Уникальный идентификатор прибора',
            'quantity' => 'Количество прибора',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getDevice()
    {
        return $this->hasOne(Device::className(), ['id' => 'device_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRepairMapSpecificEquipmentSection()
    {
        return $this->hasOne(RepairMapSpecificEquipmentSection::className(), ['id' => 'repair_map_specific_equipment_section_id']);
    }
}
