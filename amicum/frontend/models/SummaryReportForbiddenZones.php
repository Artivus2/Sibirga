<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "summary_report_forbidden_zones".
 *
 * @property int $id
 * @property string|null $date_work
 * @property int|null $shift
 * @property int|null $main_id
 * @property string|null $main_title
 * @property int|null $place_id
 * @property int|null $edge_id
 * @property int|null $object_id
 * @property int|null $place_status_id
 * @property string|null $date_time_start
 * @property string|null $date_time_end
 * @property string|null $duration
 * @property string|null $table_name
 */
class SummaryReportForbiddenZones extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'summary_report_forbidden_zones';
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
            [['date_work', 'date_time_start', 'date_time_end', 'duration'], 'safe'],
            [['shift', 'main_id', 'place_id', 'edge_id', 'object_id', 'place_status_id'], 'integer'],
            [['main_title'], 'string', 'max' => 250],
            [['table_name'], 'string', 'max' => 15],
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
            'shift' => 'Shift',
            'main_id' => 'Main ID',
            'main_title' => 'Main Title',
            'place_id' => 'Place ID',
            'edge_id' => 'Edge ID',
            'object_id' => 'Object ID',
            'place_status_id' => 'Place Status ID',
            'date_time_start' => 'Date Time Start',
            'date_time_end' => 'Date Time End',
            'duration' => 'Duration',
            'table_name' => 'Table Name',
        ];
    }
}
