<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "prod_graphic_work".
 *
 * @property int $id
 * @property string|null $year год
 * @property string|null $1 январь
 * @property string|null $2 февраль
 * @property string|null $3 март
 * @property string|null $4 апрель
 * @property string|null $5 май
 * @property string|null $6 июнь
 * @property string|null $7 июль
 * @property string|null $8 август
 * @property string|null $9 сентябрь
 * @property string|null $10 октябрь
 * @property string|null $11 ноябрь
 * @property string|null $12 декабрь
 * @property int|null $all_work_day Всего рабочих дней
 * @property int|null $all_week_end Всего праздничных и выходных дней
 * @property int|null $count_work_hours_40 Количество рабочих часов при 40-часовой рабочей неделе
 * @property int|null $count_work_hours_36 Количество рабочих часов при 36-часовой рабочей неделе
 * @property int|null $count_work_hours_24 Количество рабочих часов при 24-часовой рабочей неделе
 */
class ProdGraphicWork extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'prod_graphic_work';
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
            [['id'], 'required'],
            [['id', 'all_work_day', 'all_week_end', 'count_work_hours_40', 'count_work_hours_36', 'count_work_hours_24'], 'integer'],
            [['year'], 'safe'],
            [['1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12'], 'string', 'max' => 45],
            [['id'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'year' => 'год',
            '1' => 'январь',
            '2' => 'февраль',
            '3' => 'март',
            '4' => 'апрель',
            '5' => 'май',
            '6' => 'июнь',
            '7' => 'июль',
            '8' => 'август',
            '9' => 'сентябрь',
            '10' => 'октябрь',
            '11' => 'ноябрь',
            '12' => 'декабрь',
            'all_work_day' => 'Всего рабочих дней',
            'all_week_end' => 'Всего праздничных и выходных дней',
            'count_work_hours_40' => 'Количество рабочих часов при 40-часовой рабочей неделе',
            'count_work_hours_36' => 'Количество рабочих часов при 36-часовой рабочей неделе',
            'count_work_hours_24' => 'Количество рабочих часов при 24-часовой рабочей неделе',
        ];
    }
}
