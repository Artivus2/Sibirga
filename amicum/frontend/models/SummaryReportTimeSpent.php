<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "summary_report_time_spent".
 *
 * @property int $id
 * @property string $date_work
 * @property string $date_time_work дата
 * @property string $FIO
 * @property int $type_worker_id
 * @property string $type_worker_title
 * @property string $department_title
 * @property string $company_title
 * @property string $place_title
 * @property int $place_id
 * @property string $smena
 * @property int $worker_id
 * @property string $type_place_title
 * @property string $kind_place_title
 * @property string $place_status_title
 * @property int $type_place_id
 * @property int $kind_place_id
 * @property int $main_kind_place_id
 * @property int $department_id
 * @property int $place_status_id
 */
class SummaryReportTimeSpent extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'summary_report_time_spent';
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
            [['date_time_work'], 'safe'],
            [['type_worker_id', 'place_id', 'worker_id', 'type_place_id', 'kind_place_id', 'main_kind_place_id', 'department_id', 'place_status_id'], 'integer'],
            [['date_work'], 'string', 'max' => 10],
            [['FIO', 'place_title'], 'string', 'max' => 250],
            [['type_worker_title', 'department_title', 'company_title', 'type_place_title', 'kind_place_title', 'place_status_title'], 'string', 'max' => 255],
            [['smena'], 'string', 'max' => 45],
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
            'date_time_work' => 'дата',
            'FIO' => 'Fio',
            'type_worker_id' => 'Type Worker ID',
            'type_worker_title' => 'Type Worker Title',
            'department_title' => 'Department Title',
            'company_title' => 'Company Title',
            'place_title' => 'Place Title',
            'place_id' => 'Place ID',
            'smena' => 'Smena',
            'worker_id' => 'Worker ID',
            'type_place_title' => 'Type Place Title',
            'kind_place_title' => 'Kind Place Title',
            'place_status_title' => 'Place Status Title',
            'type_place_id' => 'Type Place ID',
            'kind_place_id' => 'Kind Place ID',
            'main_kind_place_id' => 'Main Kind Place ID',
            'department_id' => 'Department ID',
            'place_status_id' => 'Place Status ID',
        ];
    }
}
