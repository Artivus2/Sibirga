<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "union_equipment".
 *
 * @property int $id
 * @property string $title
 *
 * @property EquipmentUnion[] $equipmentUnions
 * @property Equipment[] $equipment
 */
class UnionEquipment extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'union_equipment';
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
            [['title'], 'required'],
            [['title'], 'string', 'max' => 255],
            [['title'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'title' => 'Title',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getEquipmentUnions()
    {
        return $this->hasMany(EquipmentUnion::className(), ['union_equipment_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getEquipment()
    {
        return $this->hasMany(Equipment::className(), ['id' => 'equipment_id'])->viaTable('equipment_union', ['union_equipment_id' => 'id']);
    }
}
