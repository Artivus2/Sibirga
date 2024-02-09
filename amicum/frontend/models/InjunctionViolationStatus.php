<?php
/*
 * Copyright (c) 2023. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 *
 */

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "injunction_violation_status".
 *
 * @property int $id Идентификатор таблицы (автоинкрементный)
 * @property int $injunction_violation_id Внешний ключ нарушений предписания
 * @property int $status_id Внешний ключ статусов
 * @property string $date_time
 * @property int|null $worker_id Внешний ключ работника
 *
 * @property InjunctionViolation $injunctionViolation
 * @property Status $status
 */
class InjunctionViolationStatus extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'injunction_violation_status';
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
            [['injunction_violation_id', 'status_id', 'date_time'], 'required'],
            [['injunction_violation_id', 'status_id', 'worker_id'], 'integer'],
            [['date_time'], 'safe'],
            [['injunction_violation_id'], 'exist', 'skipOnError' => true, 'targetClass' => InjunctionViolation::className(), 'targetAttribute' => ['injunction_violation_id' => 'id']],
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
            'injunction_violation_id' => 'Injunction Violation ID',
            'status_id' => 'Status ID',
            'date_time' => 'Date Time',
            'worker_id' => 'Worker ID',
        ];
    }

    /**
     * Gets query for [[InjunctionViolation]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getInjunctionViolation()
    {
        return $this->hasOne(InjunctionViolation::className(), ['id' => 'injunction_violation_id']);
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
