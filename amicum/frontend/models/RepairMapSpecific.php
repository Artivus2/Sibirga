<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "repair_map_specific".
 *
 * @property int $id Идентификатор самой таблицы (автоинкрементный)
 * @property string $title Наименование ТКР конкретного оборудования
 * @property int $equipment_id Уникальный идентификатор оборудования
 * @property int $kind_repair_id Уникальный идентификатор вида работы
 * @property int $repair_map_typical_id Уникальный идентификатор технологической карты ремонтов типовых объектов  
 * @property int $object_id Уникальный идентификатор объекта
 * @property int $brigade_id Уникальный идентификатор бригады
 *
 * @property Brigade $brigade
 * @property Equipment $equipment
 * @property KindRepair $kindRepair
 * @property Object $object
 * @property RepairMapTypical $repairMapTypical
 * @property RepairMapSpecificEquipmentSection[] $repairMapSpecificEquipmentSections
 */
class RepairMapSpecific extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'repair_map_specific';
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
            [['title', 'equipment_id', 'kind_repair_id', 'repair_map_typical_id', 'object_id', 'brigade_id'], 'required'],
            [['equipment_id', 'kind_repair_id', 'repair_map_typical_id', 'object_id', 'brigade_id'], 'integer'],
            [['title'], 'string', 'max' => 255],
            [['brigade_id'], 'exist', 'skipOnError' => true, 'targetClass' => Brigade::className(), 'targetAttribute' => ['brigade_id' => 'id']],
            [['equipment_id'], 'exist', 'skipOnError' => true, 'targetClass' => Equipment::className(), 'targetAttribute' => ['equipment_id' => 'id']],
            [['kind_repair_id'], 'exist', 'skipOnError' => true, 'targetClass' => KindRepair::className(), 'targetAttribute' => ['kind_repair_id' => 'id']],
            [['object_id'], 'exist', 'skipOnError' => true, 'targetClass' => TypicalObject::className(), 'targetAttribute' => ['object_id' => 'id']],
            [['repair_map_typical_id'], 'exist', 'skipOnError' => true, 'targetClass' => RepairMapTypical::className(), 'targetAttribute' => ['repair_map_typical_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'Идентификатор самой таблицы (автоинкрементный)',
            'title' => 'Наименование ТКР конкретного оборудования',
            'equipment_id' => 'Уникальный идентификатор оборудования',
            'kind_repair_id' => 'Уникальный идентификатор вида работы',
            'repair_map_typical_id' => 'Уникальный идентификатор технологической карты ремонтов типовых объектов  ',
            'object_id' => 'Уникальный идентификатор объекта',
            'brigade_id' => 'Уникальный идентификатор бригады',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getBrigade()
    {
        return $this->hasOne(Brigade::className(), ['id' => 'brigade_id']);
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
    public function getKindRepair()
    {
        return $this->hasOne(KindRepair::className(), ['id' => 'kind_repair_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getObject()
    {
        return $this->hasOne(TypicalObject::className(), ['id' => 'object_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRepairMapTypical()
    {
        return $this->hasOne(RepairMapTypical::className(), ['id' => 'repair_map_typical_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRepairMapSpecificEquipmentSections()
    {
        return $this->hasMany(RepairMapSpecificEquipmentSection::className(), ['repair_map_specific_id' => 'id']);
    }
}
