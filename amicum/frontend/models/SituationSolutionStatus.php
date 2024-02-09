<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "situation_solution_status".
 *
 * @property int $id ключ истории изменения статуса решения/устранения ситуации
 * @property int $situation_solution_id ключ решения ситуации
 * @property int|null $responsible_position_id ключ должности последнего сменившего статус
 * @property int|null $responsible_worker_id ключ ответственного работника последнего сменившего статус
 * @property int|null $status_id ключ последнего статуса
 * @property string|null $date_time дата и время последнего изменения статуса
 * @property string|null $description комментарий ответственного при изменении/неизменении статуса
 *
 * @property SituationSolution $situationSolution
 * @property Position $responsiblePosition
 * @property Status $status
 * @property Worker $responsibleWorker
 */
class SituationSolutionStatus extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'situation_solution_status';
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
            [['situation_solution_id'], 'required'],
            [['situation_solution_id', 'responsible_position_id', 'responsible_worker_id', 'status_id'], 'integer'],
            [['date_time'], 'safe'],
            [['description'], 'string', 'max' => 1000],
            [['situation_solution_id'], 'exist', 'skipOnError' => true, 'targetClass' => SituationSolution::className(), 'targetAttribute' => ['situation_solution_id' => 'id']],
            [['responsible_position_id'], 'exist', 'skipOnError' => true, 'targetClass' => Position::className(), 'targetAttribute' => ['responsible_position_id' => 'id']],
            [['status_id'], 'exist', 'skipOnError' => true, 'targetClass' => Status::className(), 'targetAttribute' => ['status_id' => 'id']],
            [['responsible_worker_id'], 'exist', 'skipOnError' => true, 'targetClass' => Worker::className(), 'targetAttribute' => ['responsible_worker_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'situation_solution_id' => 'Situation Solution ID',
            'responsible_position_id' => 'Responsible Position ID',
            'responsible_worker_id' => 'Responsible Worker ID',
            'status_id' => 'Status ID',
            'date_time' => 'Date Time',
            'description' => 'Description',
        ];
    }

    /**
     * Gets query for [[SituationSolution]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getSituationSolution()
    {
        return $this->hasOne(SituationSolution::className(), ['id' => 'situation_solution_id']);
    }

    /**
     * Gets query for [[ResponsiblePosition]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getResponsiblePosition()
    {
        return $this->hasOne(Position::className(), ['id' => 'responsible_position_id']);
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
     * Gets query for [[ResponsibleWorker]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getResponsibleWorker()
    {
        return $this->hasOne(Worker::className(), ['id' => 'responsible_worker_id']);
    }
}
