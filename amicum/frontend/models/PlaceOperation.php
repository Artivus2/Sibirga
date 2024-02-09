<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "place_operation".
 *
 * @property int $id Идентификатор таблицы
 * @property int $operation_id Внешний ключ к справочнику операций
 * @property int $place_id Внешний ключ к справочнику мест
 * @property int $brigade_id
 *
 * @property Operation $operation
 * @property Place $place
 * @property PlaceOperationValue[] $placeOperationValues
 */
class PlaceOperation extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'place_operation';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['operation_id', 'place_id', 'brigade_id'], 'required'],
            [['operation_id', 'place_id', 'brigade_id'], 'integer'],
            [['operation_id', 'place_id', 'brigade_id'], 'unique', 'targetAttribute' => ['operation_id', 'place_id', 'brigade_id']],
            [['operation_id'], 'exist', 'skipOnError' => true, 'targetClass' => Operation::className(), 'targetAttribute' => ['operation_id' => 'id']],
            [['place_id'], 'exist', 'skipOnError' => true, 'targetClass' => Place::className(), 'targetAttribute' => ['place_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'operation_id' => 'Operation ID',
            'place_id' => 'Place ID',
            'brigade_id' => 'Brigade ID',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOperation()
    {
        return $this->hasOne(Operation::className(), ['id' => 'operation_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPlace()
    {
        return $this->hasOne(Place::className(), ['id' => 'place_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPlaceOperationValues()
    {
        return $this->hasMany(PlaceOperationValue::className(), ['place_operation_id' => 'id']);
    }
}
