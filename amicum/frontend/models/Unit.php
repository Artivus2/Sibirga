<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "unit".
 *
 * @property int $id ключ справочника единиц измерения
 * @property string $title название единицы измерения
 * @property string $short сокращенное название единиц измерения
 * @property int $sap_id соответствие единицы измерения в sap
 *
 * @property Device[] $devices
 * @property Instrument[] $instruments
 * @property Material[] $materials
 * @property Operation[] $operations
 * @property Parameter[] $parameters
 * @property Siz[] $sizs
 */
class Unit extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'unit';
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
            [['title', 'short'], 'required'],
            [['sap_id'], 'integer'],
            [['title'], 'string', 'max' => 255],
            [['short'], 'string', 'max' => 15],
            [['title'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ключ справочника единиц измерения',
            'title' => 'название единицы измерения',
            'short' => 'сокращенное название единиц измерения',
            'sap_id' => 'соответствие единицы измерения в sap',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getDevices()
    {
        return $this->hasMany(Device::className(), ['unit_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getInstruments()
    {
        return $this->hasMany(Instrument::className(), ['unit_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMaterials()
    {
        return $this->hasMany(Material::className(), ['unit_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOperations()
    {
        return $this->hasMany(Operation::className(), ['unit_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getParameters()
    {
        return $this->hasMany(Parameter::className(), ['unit_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSizs()
    {
        return $this->hasMany(Siz::className(), ['unit_id' => 'id']);
    }
}
