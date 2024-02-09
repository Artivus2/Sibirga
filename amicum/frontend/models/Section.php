<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "section".
 *
 * @property int $id Идентификатор секции
 * @property int $title Наименование секции 
 *
 * @property EquipmentSection[] $equipmentSections
 * @property Equipment[] $equipment
 */
class Section extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'section';
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
            [['title'], 'integer'],
            [['title'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'Идентификатор секции',
            'title' => 'Наименование секции ',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getEquipmentSections()
    {
        return $this->hasMany(EquipmentSection::className(), ['section_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getEquipment()
    {
        return $this->hasMany(Equipment::className(), ['id' => 'equipment_id'])->viaTable('equipment_section', ['section_id' => 'id']);
    }
}
