<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "worker_parameter_value_temp".
 *
 * @property int $id
 * @property int $worker_parameter_id
 * @property string $date_time дата с микросекундамиDATETIME(6)DATETIME(6)
 * @property string $value
 * @property int $status_id
 * @property string|null $shift в какой смене работал/был
 * @property string|null $date_work Дата смены
 *
 * @property WorkerParameter $workerParameter
 */
class WorkerParameterValueTemp extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'worker_parameter_value_temp';
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
            [['worker_parameter_id', 'date_time', 'value', 'status_id'], 'required'],
            [['worker_parameter_id', 'status_id'], 'integer'],
            [['date_time', 'date_work'], 'safe'],
            [['value'], 'string', 'max' => 255],
            [['shift'], 'string', 'max' => 55],
            [['worker_parameter_id'], 'exist', 'skipOnError' => true, 'targetClass' => WorkerParameter::className(), 'targetAttribute' => ['worker_parameter_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'worker_parameter_id' => 'Worker Parameter ID',
            'date_time' => 'Date Time',
            'value' => 'Value',
            'status_id' => 'Status ID',
            'shift' => 'Shift',
            'date_work' => 'Date Work',
        ];
    }

    /**
     * Gets query for [[WorkerParameter]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getWorkerParameter()
    {
        return $this->hasOne(WorkerParameter::className(), ['id' => 'worker_parameter_id']);
    }
}
