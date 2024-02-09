<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "brigade_parameter_handbook_value".
 *
 * @property int $id Идентификатор таблицы
 * @property int $brigade_parameter_id Внешний ключ к таблице параметров бригады
 * @property string $date_time Дата и время добавления значения параметра
 * @property string $value Значение параметра
 * @property int $status_id Статус значения параметра
 * @property int $year Год на который задается параметр
 * @property int $month Месяц на который задается параметр
 *
 * @property BrigadeParameter $brigadeParameter
 * @property Status $status
 */
class BrigadeParameterHandbookValue extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'brigade_parameter_handbook_value';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['brigade_parameter_id', 'date_time', 'value', 'status_id'], 'required'],
            [['brigade_parameter_id', 'status_id', 'year', 'month'], 'integer'],
            [['date_time'], 'safe'],
            [['value'], 'string', 'max' => 255],
            [['brigade_parameter_id', 'date_time'], 'unique', 'targetAttribute' => ['brigade_parameter_id', 'date_time']],
            [['brigade_parameter_id'], 'exist', 'skipOnError' => true, 'targetClass' => BrigadeParameter::className(), 'targetAttribute' => ['brigade_parameter_id' => 'id']],
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
            'brigade_parameter_id' => 'Внешний ключ к таблице параметров бригады',
            'date_time' => 'Дата и время добавления значения параметра',
            'value' => 'Значение параметра',
            'status_id' => 'Статус значения параметра',
            'year' => 'Год на который задается параметр',
            'month' => 'Месяц на который задается параметр',
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
    public function getStatus()
    {
        return $this->hasOne(Status::className(), ['id' => 'status_id']);
    }
}
