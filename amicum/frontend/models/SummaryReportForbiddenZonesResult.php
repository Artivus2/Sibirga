<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "summary_report_forbidden_zones_result".
 *
 * @property int $id
 * @property string|null $date_work
 * @property string|null $name
 * @property int|null $tabel_number
 * @property int|null $department_id
 * @property string|null $department_title
 * @property string|null $company_title
 * @property int|null $place_id
 * @property string|null $place_title
 * @property string|null $duration
 */
class SummaryReportForbiddenZonesResult extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'summary_report_forbidden_zones_result';
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
            [['date_work', 'duration'], 'safe'],
            [['tabel_number', 'department_id', 'place_id'], 'integer'],
            [['name', 'department_title', 'company_title', 'place_title'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'date_work' => 'Date Work',
            'name' => 'Name',
            'tabel_number' => 'Tabel Number',
            'department_id' => 'Department ID',
            'department_title' => 'Department Title',
            'company_title' => 'Company Title',
            'place_id' => 'Place ID',
            'place_title' => 'Place Title',
            'duration' => 'Duration',
        ];
    }
}
