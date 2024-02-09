<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "view_GetSummaryWorkerByDayAndMonth".
 *
 * @property int $grafic_tabel_main_id Ключ таблицы графика выходов
 * @property int $grafic_tabel_main_month месяц, на который составляется график выходов
 * @property string $grafic_tabel_main_year год на который составляется график выходов
 * @property int $company_department_id Внешний ключ справочника подразделений
 * @property int $day День, на который составляется график выходов
 * @property int $sum_day
 * @property int $shift_id1
 * @property int $shift_id2
 * @property int $shift_id3
 * @property int $shift_id4
 */
class ViewGetSummaryWorkerByDayAndMonth extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'view_GetSummaryWorkerByDayAndMonth';
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
            [['grafic_tabel_main_id', 'grafic_tabel_main_month', 'company_department_id', 'day', 'sum_day', 'shift_id1', 'shift_id2', 'shift_id3', 'shift_id4'], 'integer'],
            [['grafic_tabel_main_month', 'grafic_tabel_main_year', 'company_department_id', 'day'], 'required'],
            [['grafic_tabel_main_year'], 'safe'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'grafic_tabel_main_id' => 'Grafic Tabel Main ID',
            'grafic_tabel_main_month' => 'Grafic Tabel Main Month',
            'grafic_tabel_main_year' => 'Grafic Tabel Main Year',
            'company_department_id' => 'Company Department ID',
            'day' => 'Day',
            'sum_day' => 'Sum Day',
            'shift_id1' => 'Shift Id1',
            'shift_id2' => 'Shift Id2',
            'shift_id3' => 'Shift Id3',
            'shift_id4' => 'Shift Id4',
        ];
    }

    /**
     * {@inheritdoc}
     * @return ViewGetSummaryWorkerByDayAndMonthQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new ViewGetSummaryWorkerByDayAndMonthQuery(get_called_class());
    }
}
