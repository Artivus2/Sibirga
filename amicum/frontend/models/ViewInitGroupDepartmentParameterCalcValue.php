<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "view_initGroupDepartmentParameterCalcValue".
 *
 * @property int $group_department_id Идентификатор таблицы
 * @property int $group_department_parameter_id Идентификатор таблицы
 * @property int $parameter_id Внешний ключ к таблице параметров
 * @property int $parameter_type_id Внешний ключ к таблице типов параметров
 * @property string $date_time Дата и время добавления значения параметра
 * @property double $value Значение вычисляемого параметра
 * @property int $status_id Идентификатор статуса 
 */
class ViewInitGroupDepartmentParameterCalcValue extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'view_initGroupDepartmentParameterCalcValue';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['group_department_id', 'group_department_parameter_id', 'parameter_id', 'parameter_type_id', 'status_id'], 'integer'],
            [['parameter_id', 'parameter_type_id'], 'required'],
            [['date_time'], 'safe'],
            [['value'], 'number'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'group_department_id' => 'Идентификатор таблицы',
            'group_department_parameter_id' => 'Идентификатор таблицы',
            'parameter_id' => 'Внешний ключ к таблице параметров',
            'parameter_type_id' => 'Внешний ключ к таблице типов параметров',
            'date_time' => 'Дата и время добавления значения параметра',
            'value' => 'Значение вычисляемого параметра',
            'status_id' => 'Идентификатор статуса
',
        ];
    }
}
