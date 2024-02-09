<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "instrument".
 *
 * @property int $id Идентификатор самой таблицы (автоинкрементный)
 * @property string $title Наименование инструмента  
 * @property int $unit_id Уникальный идентификатор единицы измерения 
 *
 * @property Unit $unit
 * @property RepairMapSpecificInstrument[] $repairMapSpecificInstruments
 * @property RepairMapTypicalInstrument[] $repairMapTypicalInstruments
 */
class Instrument extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'instrument';
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
            'id' => 'Идентификатор самой таблицы (автоинкрементный)',
            'title' => 'Наименование инструмента  ',
            'unit_id' => 'Уникальный идентификатор единицы измерения ',
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
    public function getRepairMapSpecificInstruments()
    {
        return $this->hasMany(RepairMapSpecificInstrument::className(), ['instrument_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRepairMapTypicalInstruments()
    {
        return $this->hasMany(RepairMapTypicalInstrument::className(), ['instrument_id' => 'id']);
    }
}
