<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "equipment_union".
 *
 * @property int $id
 * @property int $union_equipment_id
 * @property int $equipment_id
 *
 * @property Equipment $equipment
 * @property UnionEquipment $unionEquipment
 */
class EquipmentUnion extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'equipment_union';
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
            [['union_equipment_id', 'equipment_id'], 'required'],
            [['union_equipment_id', 'equipment_id'], 'integer'],
            [['union_equipment_id', 'equipment_id'], 'unique', 'targetAttribute' => ['union_equipment_id', 'equipment_id']],
            [['equipment_id'], 'exist', 'skipOnError' => true, 'targetClass' => Equipment::className(), 'targetAttribute' => ['equipment_id' => 'id']],
            [['union_equipment_id'], 'exist', 'skipOnError' => true, 'targetClass' => UnionEquipment::className(), 'targetAttribute' => ['union_equipment_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'union_equipment_id' => 'Union Equipment ID',
            'equipment_id' => 'Equipment ID',
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
    public function getUnionEquipment()
    {
        return $this->hasOne(UnionEquipment::className(), ['id' => 'union_equipment_id']);
    }
}
