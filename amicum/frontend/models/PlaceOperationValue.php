<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "place_operation_value".
 *
 * @property int $id Идентификатор таблицы
 * @property int $place_operation_id Внешний ключ к таблице связке(места, операции, бригады
 * @property double $value Значение парамера
 * @property string $date Дата на которую устанавливается значение параметра
 * @property int $status_id Внешний ключ к справочнику статусов
 *
 * @property PlaceOperation $placeOperation
 * @property Status $status
 */
class PlaceOperationValue extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'place_operation_value';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['place_operation_id', 'value', 'date', 'status_id'], 'required'],
            [['place_operation_id', 'status_id'], 'integer'],
            [['value'], 'number'],
            [['date'], 'safe'],
            [['place_operation_id'], 'exist', 'skipOnError' => true, 'targetClass' => PlaceOperation::className(), 'targetAttribute' => ['place_operation_id' => 'id']],
            [['status_id'], 'exist', 'skipOnError' => true, 'targetClass' => Status::className(), 'targetAttribute' => ['status_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'place_operation_id' => 'Place Operation ID',
            'value' => 'Value',
            'date' => 'Date',
            'status_id' => 'Status ID',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPlaceOperation()
    {
        return $this->hasOne(PlaceOperation::className(), ['id' => 'place_operation_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStatus()
    {
        return $this->hasOne(Status::className(), ['id' => 'status_id']);
    }
}
