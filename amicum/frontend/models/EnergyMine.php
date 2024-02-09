<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "energy_mine".
 *
 * @property int $id
 * @property string $title
 * @property int $object_id
 * @property int $main_from_id
 * @property int $main_to_id
 *
 * @property Main $mainFrom
 * @property Main $mainTo
 * @property Object $object
 * @property EnergyMineFunction[] $energyMineFunctions
 * @property EnergyMineParameter[] $energyMineParameters
 */
class EnergyMine extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'energy_mine';
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
            [['title', 'object_id', 'main_from_id', 'main_to_id'], 'required'],
            [['object_id', 'main_from_id', 'main_to_id'], 'integer'],
            [['title'], 'string', 'max' => 45],
            [['main_from_id'], 'exist', 'skipOnError' => true, 'targetClass' => Main::className(), 'targetAttribute' => ['main_from_id' => 'id']],
            [['main_to_id'], 'exist', 'skipOnError' => true, 'targetClass' => Main::className(), 'targetAttribute' => ['main_to_id' => 'id']],
            [['object_id'], 'exist', 'skipOnError' => true, 'targetClass' => TypicalObject::className(), 'targetAttribute' => ['object_id' => 'id']],
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
            'object_id' => 'Object ID',
            'main_from_id' => 'Main From ID',
            'main_to_id' => 'Main To ID',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMainFrom()
    {
        return $this->hasOne(Main::className(), ['id' => 'main_from_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMainTo()
    {
        return $this->hasOne(Main::className(), ['id' => 'main_to_id']);
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
    public function getEnergyMineFunctions()
    {
        return $this->hasMany(EnergyMineFunction::className(), ['energy_mine_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getEnergyMineParameters()
    {
        return $this->hasMany(EnergyMineParameter::className(), ['energy_mine_id' => 'id']);
    }
}
