<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "worker_parameter_value_history".
 *
 * @property int $id BIGINT(20)
 * @property int $worker_parameter_id
 * @property string $date_time дата с микросекундамиDATETIME(6)DATETIME(6
 * @property string $value
 * @property int $status_id
 * @property string|null $shift    /
 * @property string|null $date_work    
 *
 * @property WorkerParameter $workerParameter
 * @property Status $status
 */
class WorkerParameterValueHistory extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'worker_parameter_value_history';
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
            [['worker_parameter_id', 'date_time'], 'unique', 'targetAttribute' => ['worker_parameter_id', 'date_time']],
            [['worker_parameter_id'], 'exist', 'skipOnError' => true, 'targetClass' => WorkerParameter::className(), 'targetAttribute' => ['worker_parameter_id' => 'id']],
            [['status_id'], 'exist', 'skipOnError' => true, 'targetClass' => Status::className(), 'targetAttribute' => ['status_id' => 'id']],
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

    /**
     * Gets query for [[Status]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getStatus()
    {
        return $this->hasOne(Status::className(), ['id' => 'status_id']);
    }
}
