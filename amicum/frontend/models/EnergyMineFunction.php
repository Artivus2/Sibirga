<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "energy_mine_function".
 *
 * @property int $id
 * @property int $energy_mine_id
 * @property int $function_id
 *
 * @property EnergyMine $energyMine
 * @property Func $function
 */
class EnergyMineFunction extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'energy_mine_function';
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
            [['energy_mine_id', 'function_id'], 'required'],
            [['energy_mine_id', 'function_id'], 'integer'],
            [['energy_mine_id'], 'exist', 'skipOnError' => true, 'targetClass' => EnergyMine::className(), 'targetAttribute' => ['energy_mine_id' => 'id']],
            [['function_id'], 'exist', 'skipOnError' => true, 'targetClass' => Func::className(), 'targetAttribute' => ['function_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'energy_mine_id' => 'Energy Mine ID',
            'function_id' => 'Function ID',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getEnergyMine()
    {
        return $this->hasOne(EnergyMine::className(), ['id' => 'energy_mine_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getFunction()
    {
        return $this->hasOne(Func::className(), ['id' => 'function_id']);
    }
}
