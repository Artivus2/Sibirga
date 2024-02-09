<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "event_pb_worker".
 *
 * @property int $id
 * @property int $event_pb_id событие/несчастный случай
 * @property int $worker_id ключ пострадавшего работника
 * @property int $role_id ключ роли пострадавшего
 * @property int $outcome_id ключ последствия 
 * @property int $value_day количество дней нетрудоспособности
 * @property int $position_id
 * @property int $experience стаж
 * @property string $birthday дата рождения
 *
 * @property EventPb $eventPb
 * @property Worker $worker
 * @property Role $role
 * @property Outcome $outcome
 * @property Position $position
 */
class EventPbWorker extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'event_pb_worker';
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
            [['event_pb_id', 'worker_id', 'position_id'], 'required'],
            [['event_pb_id', 'worker_id', 'role_id', 'outcome_id', 'value_day', 'position_id', 'experience'], 'integer'],
            [['birthday'], 'safe'],
            [['event_pb_id', 'worker_id'], 'unique', 'targetAttribute' => ['event_pb_id', 'worker_id']],
            [['event_pb_id'], 'exist', 'skipOnError' => true, 'targetClass' => EventPb::className(), 'targetAttribute' => ['event_pb_id' => 'id']],
            [['worker_id'], 'exist', 'skipOnError' => true, 'targetClass' => Worker::className(), 'targetAttribute' => ['worker_id' => 'id']],
            [['role_id'], 'exist', 'skipOnError' => true, 'targetClass' => Role::className(), 'targetAttribute' => ['role_id' => 'id']],
            [['outcome_id'], 'exist', 'skipOnError' => true, 'targetClass' => Outcome::className(), 'targetAttribute' => ['outcome_id' => 'id']],
            [['position_id'], 'exist', 'skipOnError' => true, 'targetClass' => Position::className(), 'targetAttribute' => ['position_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'event_pb_id' => 'событие/несчастный случай',
            'worker_id' => 'ключ пострадавшего работника',
            'role_id' => 'ключ роли пострадавшего',
            'outcome_id' => 'ключ последствия ',
            'value_day' => 'количество дней нетрудоспособности',
            'position_id' => 'Position ID',
            'experience' => 'стаж',
            'birthday' => 'дата рождения',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getEventPb()
    {
        return $this->hasOne(EventPb::className(), ['id' => 'event_pb_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getWorker()
    {
        return $this->hasOne(Worker::className(), ['id' => 'worker_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRole()
    {
        return $this->hasOne(Role::className(), ['id' => 'role_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOutcome()
    {
        return $this->hasOne(Outcome::className(), ['id' => 'outcome_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPosition()
    {
        return $this->hasOne(Position::className(), ['id' => 'position_id']);
    }
}
