<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "view_initGroupDepartmentParameterHandbookValue".
 *
 * @property int $group_department_id Идентификатор таблицы
 * @property int $group_department_parameter_id Идентификатор таблицы
 * @property int $parameter_id Внешний ключ к таблице параметров
 * @property int $parameter_type_id Внешний ключ к таблице типов параметров
 * @property string $date_time Дата и время добавления значения параметра
 * @property string $value Значение параметра
 * @property int $status_id Статус значения параметра
 * @property int $year Год на который задается параметр
 * @property int $month Месяц на который задается параметр
 */
class ViewInitGroupDepartmentParameterHandbookValue extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'view_initGroupDepartmentParameterHandbookValue';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['group_department_id', 'group_department_parameter_id', 'parameter_id', 'parameter_type_id', 'status_id', 'year', 'month'], 'integer'],
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
            'group_department_id' => 'Идентификатор таблицы',
            'group_department_parameter_id' => 'Идентификатор таблицы',
            'parameter_id' => 'Внешний ключ к таблице параметров',
            'parameter_type_id' => 'Внешний ключ к таблице типов параметров',
            'date_time' => 'Дата и время добавления значения параметра',
            'value' => 'Значение параметра',
            'status_id' => 'Статус значения параметра',
            'year' => 'Год на который задается параметр',
            'month' => 'Месяц на который задается параметр',
        ];
    }
}
