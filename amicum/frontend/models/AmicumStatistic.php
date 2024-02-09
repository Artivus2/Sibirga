<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "amicum_statistic".
 *
 * @property int $id
 * @property string|null $method_name Название скрпита
 * @property string|null $date_time_start Дата и время начала выполнения скрипта
 * @property string|null $date_time_end Дата и время окончания выполнения скрипта
 * @property float|null $duration Время выполения скрипта
 * @property float|null $max_memory_peak Максимальная память в пике
 * @property string|null $debug
 * @property string|null $warnings
 * @property string|null $errors
 * @property int|null $number_rows_affected Количество затронутых строк выборки
 */
class AmicumStatistic extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'amicum_statistic';
    }

    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
        return Yii::$app->get('db_amicum_log');
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['date_time_start', 'date_time_end'], 'safe'],
            [['duration', 'max_memory_peak'], 'number'],
            [['debug', 'warnings', 'errors'], 'string'],
            [['number_rows_affected'], 'integer'],
            [['method_name'], 'string', 'max' => 45],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'method_name' => 'Method Name',
            'date_time_start' => 'Date Time Start',
            'date_time_end' => 'Date Time End',
            'duration' => 'Duration',
            'max_memory_peak' => 'Max Memory Peak',
            'debug' => 'Debug',
            'warnings' => 'Warnings',
            'errors' => 'Errors',
            'number_rows_affected' => 'Number Rows Affected',
        ];
    }
}
