<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "brigade_parameter".
 *
 * @property int $id Идентификатор таблицы
 * @property int $brigade_id Внешний ключ к таблице бригад
 * @property int $parameter_id Внешний ключ к таблице параметров
 * @property int $parameter_type_id Внешний ключ к таблице типов параметров
 *
 * @property BrigadeParameterCalcValue[] $brigadeParameterCalcValues
 * @property BrigadeParameterHandbookValue[] $brigadeParameterHandbookValues
 */
class BrigadeParameter extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'brigade_parameter';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['brigade_id', 'parameter_id', 'parameter_type_id'], 'required'],
            [['brigade_id', 'parameter_id', 'parameter_type_id'], 'integer'],
            [['brigade_id', 'parameter_id', 'parameter_type_id'], 'unique', 'targetAttribute' => ['brigade_id', 'parameter_id', 'parameter_type_id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'Идентификатор таблицы',
            'brigade_id' => 'Внешний ключ к таблице бригад',
            'parameter_id' => 'Внешний ключ к таблице параметров',
            'parameter_type_id' => 'Внешний ключ к таблице типов параметров',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getBrigadeParameterCalcValues()
    {
        return $this->hasMany(BrigadeParameterCalcValue::className(), ['brigade_parameter_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getBrigadeParameterHandbookValues()
    {
        return $this->hasMany(BrigadeParameterHandbookValue::className(), ['brigade_parameter_id' => 'id']);
    }
}
