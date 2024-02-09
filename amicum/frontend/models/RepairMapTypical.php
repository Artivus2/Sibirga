<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "repair_map_typical".
 *
 * @property int $id Идентификатор самой таблицы (автоинкрементный)\\n
 * @property string $title Наименование ТКР типового объекта
 * @property int $kind_repair_id Уникальный идентификатор вид ремонта в ТКР
 * @property int $object_id Уникальный идентификатор объекта
 *
 * @property RepairMapSpecific[] $repairMapSpecifics
 * @property KindRepair $kindRepair
 * @property Object $object
 * @property RepairMapTypicalEquipmentSection[] $repairMapTypicalEquipmentSections
 */
class RepairMapTypical extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'repair_map_typical';
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
            [['title', 'kind_repair_id', 'object_id'], 'required'],
            [['kind_repair_id', 'object_id'], 'integer'],
            [['title'], 'string', 'max' => 255],
            [['kind_repair_id'], 'exist', 'skipOnError' => true, 'targetClass' => KindRepair::className(), 'targetAttribute' => ['kind_repair_id' => 'id']],
            [['object_id'], 'exist', 'skipOnError' => true, 'targetClass' => TypicalObject::className(), 'targetAttribute' => ['object_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'Идентификатор самой таблицы (автоинкрементный)\\\\n',
            'title' => 'Наименование ТКР типового объекта',
            'kind_repair_id' => 'Уникальный идентификатор вид ремонта в ТКР',
            'object_id' => 'Уникальный идентификатор объекта',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRepairMapSpecifics()
    {
        return $this->hasMany(RepairMapSpecific::className(), ['repair_map_typical_id' => 'id']);
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
    public function getRepairMapTypicalEquipmentSections()
    {
        return $this->hasMany(RepairMapTypicalEquipmentSection::className(), ['repair_map_typical_id' => 'id']);
    }
}
