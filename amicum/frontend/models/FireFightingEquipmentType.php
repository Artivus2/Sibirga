<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "fire_fighting_equipment_type".
 *
 * @property int $id Идентификатор типа пожарной безопасности
 * @property string $title Наименование типа средства пожарной безопасности
 *
 * @property FireFightingEquipment[] $fireFightingEquipments
 */
class FireFightingEquipmentType extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'fire_fighting_equipment_type';
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
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'Идентификатор типа пожарной безопасности',
            'title' => 'Наименование типа средства пожарной безопасности',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getFireFightingEquipments()
    {
        return $this->hasMany(FireFightingEquipment::className(), ['fire_fighting_equipment_type_id' => 'id']);
    }
}
