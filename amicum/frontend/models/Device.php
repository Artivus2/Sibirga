<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "device".
 *
 * @property int $id Идентификатор прибора
 * @property string $title Наименование прибора \\n
 * @property int $unit_id Уникальный идентификатор единицы измерения
 *
 * @property Unit $unit
 * @property RepairMapSpecificDevice[] $repairMapSpecificDevices
 * @property RepairMapTypicalDevice[] $repairMapTypicalDevices
 */
class Device extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'device';
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
            [['title'], 'unique'],
            [['unit_id'], 'exist', 'skipOnError' => true, 'targetClass' => Unit::className(), 'targetAttribute' => ['unit_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'Идентификатор прибора',
            'title' => 'Наименование прибора \\\\n',
            'unit_id' => 'Уникальный идентификатор единицы измерения',
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
    public function getRepairMapSpecificDevices()
    {
        return $this->hasMany(RepairMapSpecificDevice::className(), ['device_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRepairMapTypicalDevices()
    {
        return $this->hasMany(RepairMapTypicalDevice::className(), ['device_id' => 'id']);
    }
}
