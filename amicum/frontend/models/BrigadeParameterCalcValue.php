<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "brigade_parameter_calc_value".
 *
 * @property int $id Идентификатор таблицы
 * @property int $brigade_parameter_id Идентификатор параметра бригады
 * @property int $shift_id Идентификатор смены
 * @property int $status_id Идентификатор статуса 
 * @property double $value Значение вычисляемого параметра
 * @property string $date Дата на которую заполнялся параметр
 * @property string $date_time Дата и время добавления значения параметра
 *
 * @property BrigadeParameter $brigadeParameter
 * @property Shift $shift
 * @property Status $status
 */
class BrigadeParameterCalcValue extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'brigade_parameter_calc_value';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['brigade_parameter_id', 'shift_id', 'status_id', 'value', 'date', 'date_time'], 'required'],
            [['brigade_parameter_id', 'shift_id', 'status_id'], 'integer'],
            [['value'], 'number'],
            [['date', 'date_time'], 'safe'],
            [['brigade_parameter_id', 'date_time'], 'unique', 'targetAttribute' => ['brigade_parameter_id', 'date_time']],
            [['brigade_parameter_id'], 'exist', 'skipOnError' => true, 'targetClass' => BrigadeParameter::className(), 'targetAttribute' => ['brigade_parameter_id' => 'id']],
            [['shift_id'], 'exist', 'skipOnError' => true, 'targetClass' => Shift::className(), 'targetAttribute' => ['shift_id' => 'id']],
            [['status_id'], 'exist', 'skipOnError' => true, 'targetClass' => Status::className(), 'targetAttribute' => ['status_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'Идентификатор таблицы',
            'brigade_parameter_id' => 'Идентификатор параметра бригады',
            'shift_id' => 'Идентификатор смены',
            'status_id' => 'Идентификатор статуса
',
            'value' => 'Значение вычисляемого параметра',
            'date' => 'Дата на которую заполнялся параметр',
            'date_time' => 'Дата и время добавления значения параметра',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getBrigadeParameter()
    {
        return $this->hasOne(BrigadeParameter::className(), ['id' => 'brigade_parameter_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getShift()
    {
        return $this->hasOne(Shift::className(), ['id' => 'shift_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStatus()
    {
        return $this->hasOne(Status::className(), ['id' => 'status_id']);
    }
}
