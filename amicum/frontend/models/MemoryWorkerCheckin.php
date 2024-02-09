<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "memory_worker_checkin".
 *
 * @property int $mine_id
 * @property int $worker_id
 * @property string $date_time_checkIn
 * @property int $tabel_number
 * @property string $FIO
 * @property int $department_id
 * @property string $department_title
 * @property string $position_title
 * @property int $company_id
 * @property string $company_title
 * @property int $place_id
 * @property string $place_title
 */
class MemoryWorkerCheckin extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'memory_worker_checkin';
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
            [['mine_id', 'worker_id', 'tabel_number', 'department_id', 'company_id', 'place_id'], 'integer'],
            [['date_time_checkIn', 'FIO', 'department_title', 'position_title', 'company_title', 'place_title'], 'string', 'max' => 45],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'mine_id' => 'Mine ID',
            'worker_id' => 'Worker ID',
            'date_time_checkIn' => 'Date Time Check In',
            'tabel_number' => 'Tabel Number',
            'FIO' => 'Fio',
            'department_id' => 'Department ID',
            'department_title' => 'Department Title',
            'position_title' => 'Position Title',
            'company_id' => 'Company ID',
            'company_title' => 'Company Title',
            'place_id' => 'Place ID',
            'place_title' => 'Place Title',
        ];
    }
}
