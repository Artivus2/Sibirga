<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "summary_report_transport_history".
 *
 * @property int $id
 * @property string $date_work
 * @property string $date_time_work Дата
 * @property string $equipment_title
 * @property string $type_equipment_title
 * @property string $company_title
 * @property string $place_title
 * @property int $place_id
 * @property int $equipment_id
 * @property string $type_place_title
 * @property string $kind_place_title
 * @property string $place_status_title
 * @property int $type_place_id
 * @property int $kind_place_id
 * @property int $main_kind_place_id
 * @property int $place_status_id
 */
class SummaryReportTransportHistory extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'summary_report_transport_history';
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
            [['place_id', 'equipment_id', 'type_place_id', 'kind_place_id', 'main_kind_place_id', 'place_status_id'], 'integer'],
            [['date_work'], 'string', 'max' => 10],
            [['equipment_title', 'type_equipment_title', 'company_title', 'type_place_title', 'kind_place_title', 'place_status_title'], 'string', 'max' => 255],
            [['place_title'], 'string', 'max' => 250],
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
            'date_time_work' => 'Дата',
            'equipment_title' => 'Equipment Title',
            'type_equipment_title' => 'Type Equipment Title',
            'company_title' => 'Company Title',
            'place_title' => 'Place Title',
            'place_id' => 'Place ID',
            'equipment_id' => 'Equipment ID',
            'type_place_title' => 'Type Place Title',
            'kind_place_title' => 'Kind Place Title',
            'place_status_title' => 'Place Status Title',
            'type_place_id' => 'Type Place ID',
            'kind_place_id' => 'Kind Place ID',
            'main_kind_place_id' => 'Main Kind Place ID',
            'place_status_id' => 'Place Status ID',
        ];
    }
}
