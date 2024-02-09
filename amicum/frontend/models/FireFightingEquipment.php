<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "fire_fighting_equipment".
 *
 * @property int $id Идентификатор средствапожарной безопасности (автоинкрементный)
 * @property string $title Наименование средства пожарной безопасности
 * @property int $unit_id единица измерения
 *
 * @property Unit $unit
 * @property FireFightingEquipmentSpecific[] $fireFightingEquipmentSpecifics
 * @property FireFightingObject[] $fireFightingObjects
 */
class FireFightingEquipment extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'fire_fighting_equipment';
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
            [['title', 'unit_id'], 'required'],
            [['unit_id'], 'integer'],
            [['title'], 'string', 'max' => 255],
            [['unit_id'], 'exist', 'skipOnError' => true, 'targetClass' => Unit::className(), 'targetAttribute' => ['unit_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'Идентификатор средствапожарной безопасности (автоинкрементный)',
            'title' => 'Наименование средства пожарной безопасности',
            'unit_id' => 'единица измерения',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUnit()
    {
        return $this->hasOne(Unit::className(), ['id' => 'unit_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getFireFightingEquipmentSpecifics()
    {
        return $this->hasMany(FireFightingEquipmentSpecific::className(), ['fire_fighting_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getFireFightingObjects()
    {
        return $this->hasMany(FireFightingObject::className(), ['fire_fighting_equipment_id' => 'id']);
    }
}
