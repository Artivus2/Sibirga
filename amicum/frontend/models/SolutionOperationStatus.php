<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "solution_operation_status".
 *
 * @property int $id ключ истории изменения статуса решения/устранения ситуации
 * @property int|null $worker_id ключ ответственного работника последнего сменившего статус
 * @property int $status_id ключ последнего статуса
 * @property string $date_time дата и время последнего изменения статуса
 * @property string|null $description комментарий ответственного при изменении/неизменении статуса
 * @property int $solution_operation_id
 *
 * @property SolutionOperation $solutionOperation
 * @property Status $status
 * @property Worker $worker
 */
class SolutionOperationStatus extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'solution_operation_status';
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
            [['worker_id', 'status_id', 'solution_operation_id'], 'integer'],
            [['status_id', 'date_time', 'solution_operation_id'], 'required'],
            [['date_time'], 'safe'],
            [['description'], 'string', 'max' => 1000],
            [['solution_operation_id'], 'exist', 'skipOnError' => true, 'targetClass' => SolutionOperation::className(), 'targetAttribute' => ['solution_operation_id' => 'id']],
            [['status_id'], 'exist', 'skipOnError' => true, 'targetClass' => Status::className(), 'targetAttribute' => ['status_id' => 'id']],
            [['worker_id'], 'exist', 'skipOnError' => true, 'targetClass' => Worker::className(), 'targetAttribute' => ['worker_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'worker_id' => 'Worker ID',
            'status_id' => 'Status ID',
            'date_time' => 'Date Time',
            'description' => 'Description',
            'solution_operation_id' => 'Solution Operation ID',
        ];
    }

    /**
     * Gets query for [[SolutionOperation]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getSolutionOperation()
    {
        return $this->hasOne(SolutionOperation::className(), ['id' => 'solution_operation_id']);
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

    /**
     * Gets query for [[Worker]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getWorker()
    {
        return $this->hasOne(Worker::className(), ['id' => 'worker_id']);
    }
}