<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "equipment_unity".
 *
 * @property int $equipment_id ключ оборудования
 * @property string|null $config_json конфигурация
 *
 * @property Equipment $equipment
 */
class EquipmentUnity extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'equipment_unity';
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
            [['equipment_id'], 'required'],
            [['equipment_id'], 'integer'],
            [['config_json'], 'string'],
            [['equipment_id'], 'unique'],
            [['equipment_id'], 'exist', 'skipOnError' => true, 'targetClass' => Equipment::className(), 'targetAttribute' => ['equipment_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'equipment_id' => 'Equipment ID',
            'config_json' => 'Config Json',
        ];
    }

    /**
     * Gets query for [[Equipment]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getEquipment()
    {
        return $this->hasOne(Equipment::className(), ['id' => 'equipment_id']);
    }
}
