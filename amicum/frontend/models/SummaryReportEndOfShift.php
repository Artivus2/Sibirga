<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "summary_report_end_of_shift".
 *
 * @property int $id
 * @property string $date_work
 * @property string $date_time Дата;
 * @property string $FIO
 * @property int $worker_object_id
 * @property string $department_title
 * @property string $company_title
 * @property string $tabel_number
 * @property string $smena
 * @property int $worker_id
 * @property int $department_id
 * @property int $company_id
 */
class SummaryReportEndOfShift extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'summary_report_end_of_shift';
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
            [['date_time'], 'safe'],
            [['worker_object_id', 'worker_id', 'department_id', 'company_id'], 'integer'],
            [['date_work'], 'string', 'max' => 10],
            [['FIO'], 'string', 'max' => 152],
            [['department_title', 'company_title'], 'string', 'max' => 255],
            [['tabel_number'], 'string', 'max' => 20],
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
            'date_time' => 'Дата;',
            'FIO' => 'Fio',
            'worker_object_id' => 'Worker Object ID',
            'department_title' => 'Department Title',
            'company_title' => 'Company Title',
            'tabel_number' => 'Tabel Number',
            'smena' => 'Smena',
            'worker_id' => 'Worker ID',
            'department_id' => 'Department ID',
            'company_id' => 'Company ID',
        ];
    }
}
