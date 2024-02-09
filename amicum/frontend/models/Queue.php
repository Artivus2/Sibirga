<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "queue".
 *
 * @property int $queue_id уникальный идентификатор очереди
 * @property string $queue_date_time
 * @property string $queue_func функция для выполнения
 * @property int $queue_time_alive Время жизни задачи в очереди (0 зациклена и не удаляется, 1 -выполняется разово, 2 и т.д. количество раз выполнения задачи в очереди)
 * @property int $queue_repeat_value Количество фактических повторений
 * @property string $queue_parent Функция, которая производит внесение в очередь
 */
class Queue extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'queue';
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
            [['queue_date_time'], 'safe'],
            [['queue_func'], 'required'],
            [['queue_time_alive', 'queue_repeat_value'], 'integer'],
            [['queue_func'], 'string', 'max' => 512],
            [['queue_parent'], 'string', 'max' => 256],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'queue_id' => 'уникальный идентификатор очереди',
            'queue_date_time' => 'Queue Date Time',
            'queue_func' => 'функция для выполнения',
            'queue_time_alive' => 'Время жизни задачи в очереди (0 зациклена и не удаляется, 1 -выполняется разово, 2 и т.д. количество раз выполнения задачи в очереди)',
            'queue_repeat_value' => 'Количество фактических повторений',
            'queue_parent' => 'Функция, которая производит внесение в очередь',
        ];
    }
}
