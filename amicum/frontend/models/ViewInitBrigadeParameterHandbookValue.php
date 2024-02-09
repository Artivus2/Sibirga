<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "view_initBrigadeParameterHandbookValue".
 *
 * @property int $brigade_id
 * @property int $brigade_parameter_id
 * @property int $parameter_id
 * @property int $parameter_type_id
 * @property string $date_time Дата и время добавления значения параметра
 * @property string $value Значение параметра
 * @property int $status_id Статус значения параметра
 * @property int $year Год на который задается параметр
 * @property int $month Месяц на который задается параметр
 */
class ViewInitBrigadeParameterHandbookValue extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'view_initBrigadeParameterHandbookValue';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['brigade_id', 'brigade_parameter_id', 'parameter_id', 'parameter_type_id', 'status_id', 'year', 'month'], 'integer'],
            [['parameter_id', 'parameter_type_id'], 'required'],
            [['date_time'], 'safe'],
            [['value'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'brigade_id' => 'Brigade ID',
            'brigade_parameter_id' => 'Brigade Parameter ID',
            'parameter_id' => 'Parameter ID',
            'parameter_type_id' => 'Parameter Type ID',
            'date_time' => 'Дата и время добавления значения параметра',
            'value' => 'Значение параметра',
            'status_id' => 'Статус значения параметра',
            'year' => 'Год на который задается параметр',
            'month' => 'Месяц на который задается параметр',
        ];
    }

    /**
     * {@inheritdoc}
     * @return ViewInitBrigadeParameterHandbookValueQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new ViewInitBrigadeParameterHandbookValueQuery(get_called_class());
    }
}
