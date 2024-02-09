<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "violator".
 *
 * @property int $id Идентификатор таблицы (автоинкрементный)
 * @property int $injunction_violation_id Внешний идентификатор нарушений предписаний
 * @property int $worker_id Внешний ключ работника ответсвтенного за нарушение
 *
 * @property InjunctionViolation $injunctionViolation
 * @property Worker $worker
 */
class Violator extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'violator';
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
            [['injunction_violation_id', 'worker_id'], 'required'],
            [['injunction_violation_id', 'worker_id'], 'integer'],
            [['injunction_violation_id'], 'exist', 'skipOnError' => true, 'targetClass' => InjunctionViolation::className(), 'targetAttribute' => ['injunction_violation_id' => 'id']],
            [['worker_id'], 'exist', 'skipOnError' => true, 'targetClass' => Worker::className(), 'targetAttribute' => ['worker_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'Идентификатор таблицы (автоинкрементный)',
            'injunction_violation_id' => 'Внешний идентификатор нарушений предписаний',
            'worker_id' => 'Внешний ключ работника ответсвтенного за нарушение',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getInjunctionViolation()
    {
        return $this->hasOne(InjunctionViolation::className(), ['id' => 'injunction_violation_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getWorker()
    {
        return $this->hasOne(Worker::className(), ['id' => 'worker_id']);
    }

    /******************** Связи созданные вручную ********************/
    public function getWorkerEmployee()
    {
        return $this->hasOne(Employee::className(), ['id' => 'employee_id'])->via('worker');
    }

    public function getWorkerPosition()
    {
        return $this->hasOne(Position::className(), ['id' => 'position_id'])->via('worker');
    }
}
