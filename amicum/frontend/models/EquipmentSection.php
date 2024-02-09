<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "equipment_section".
 *
 * @property int $id Идентификатор самой таблицы (автоинкрементный)
 * @property int $equipment_id Уникальный идентификатор оборудования
 * @property int $section_id Уникальный идентификатор секции
 *
 * @property Equipment $equipment
 * @property Section $section
 * @property Reason[] $reasons
 * @property RepairMapSpecificEquipmentSection[] $repairMapSpecificEquipmentSections
 * @property RepairMapTypicalEquipmentSection[] $repairMapTypicalEquipmentSections
 */
class EquipmentSection extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'equipment_section';
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
            [['equipment_id', 'section_id'], 'required'],
            [['equipment_id', 'section_id'], 'integer'],
            [['equipment_id', 'section_id'], 'unique', 'targetAttribute' => ['equipment_id', 'section_id']],
            [['equipment_id'], 'exist', 'skipOnError' => true, 'targetClass' => Equipment::className(), 'targetAttribute' => ['equipment_id' => 'id']],
            [['section_id'], 'exist', 'skipOnError' => true, 'targetClass' => Section::className(), 'targetAttribute' => ['section_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'Идентификатор самой таблицы (автоинкрементный)',
            'equipment_id' => 'Уникальный идентификатор оборудования',
            'section_id' => 'Уникальный идентификатор секции',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getEquipment()
    {
        return $this->hasOne(Equipment::className(), ['id' => 'equipment_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSection()
    {
        return $this->hasOne(Section::className(), ['id' => 'section_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getReasons()
    {
        return $this->hasMany(Reason::className(), ['equipment_section_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRepairMapSpecificEquipmentSections()
    {
        return $this->hasMany(RepairMapSpecificEquipmentSection::className(), ['equipment_section_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRepairMapTypicalEquipmentSections()
    {
        return $this->hasMany(RepairMapTypicalEquipmentSection::className(), ['equipment_section_id' => 'id']);
    }
}
