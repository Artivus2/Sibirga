<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "amicum_synchronization".
 *
 * @property int $id
 * @property string $method_name Название скрпита
 * @property string $date_time_start Дата и время начала выполнения скрипта
 * @property string $date_time_end Дата и время окончания выполнения скрипта
 * @property double $duration Время выполения скрипта
 * @property double $max_memory_peak Максимальная память в пике
 * @property string $debug
 * @property string $warnings
 * @property string $errors
 * @property int $number_rows_affected Количество затронутых строк выборки
 */
class AmicumSynchronization extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'amicum_synchronization';
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
            'method_name' => 'Название скрпита',
            'date_time_start' => 'Дата и время начала выполнения скрипта',
            'date_time_end' => 'Дата и время окончания выполнения скрипта',
            'duration' => 'Время выполения скрипта',
            'max_memory_peak' => 'Максимальная память в пике',
            'debug' => 'Debug',
            'warnings' => 'Warnings',
            'errors' => 'Errors',
            'number_rows_affected' => 'Количество затронутых строк выборки',
        ];
    }
}
