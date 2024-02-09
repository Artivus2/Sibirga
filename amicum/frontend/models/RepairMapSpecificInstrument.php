<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "repair_map_specific_instrument".
 *
 * @property int $id
 * @property int $repair_map_specific_equipment_section_id
 * @property int $instrument_id
 * @property string $quantity
 *
 * @property Instrument $instrument
 * @property RepairMapSpecificEquipmentSection $repairMapSpecificEquipmentSection
 */
class RepairMapSpecificInstrument extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'repair_map_specific_instrument';
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
            [['repair_map_specific_equipment_section_id', 'instrument_id', 'quantity'], 'required'],
            [['repair_map_specific_equipment_section_id', 'instrument_id'], 'integer'],
            [['quantity'], 'string', 'max' => 55],
            [['instrument_id'], 'exist', 'skipOnError' => true, 'targetClass' => Instrument::className(), 'targetAttribute' => ['instrument_id' => 'id']],
            [['repair_map_specific_equipment_section_id'], 'exist', 'skipOnError' => true, 'targetClass' => RepairMapSpecificEquipmentSection::className(), 'targetAttribute' => ['repair_map_specific_equipment_section_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'repair_map_specific_equipment_section_id' => 'Repair Map Specific Equipment Section ID',
            'instrument_id' => 'Instrument ID',
            'quantity' => 'Quantity',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getInstrument()
    {
        return $this->hasOne(Instrument::className(), ['id' => 'instrument_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRepairMapSpecificEquipmentSection()
    {
        return $this->hasOne(RepairMapSpecificEquipmentSection::className(), ['id' => 'repair_map_specific_equipment_section_id']);
    }
}
