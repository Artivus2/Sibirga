<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "repair_map_typical_instrument".
 *
 * @property int $id Идентификатор  самой таблицы (автоинкрементный)
 * @property int $repair_map_typical_equipment_section_id Уникальный идентификатор секции оборудования из списка ТКР типовых объектов \\n
 * @property int $instrument_id Уникальный идентификатор инструмента
 * @property string $quantity Количество инструментов
 *
 * @property Instrument $instrument
 * @property RepairMapTypicalEquipmentSection $repairMapTypicalEquipmentSection
 */
class RepairMapTypicalInstrument extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'repair_map_typical_instrument';
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
            [['repair_map_typical_equipment_section_id', 'instrument_id'], 'required'],
            [['repair_map_typical_equipment_section_id', 'instrument_id'], 'integer'],
            [['quantity'], 'string', 'max' => 55],
            [['instrument_id'], 'exist', 'skipOnError' => true, 'targetClass' => Instrument::className(), 'targetAttribute' => ['instrument_id' => 'id']],
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
            'instrument_id' => 'Уникальный идентификатор инструмента',
            'quantity' => 'Количество инструментов',
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
    public function getRepairMapTypicalEquipmentSection()
    {
        return $this->hasOne(RepairMapTypicalEquipmentSection::className(), ['id' => 'repair_map_typical_equipment_section_id']);
    }
}
