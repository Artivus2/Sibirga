<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "correct_measures".
 *
 * @property int $id Идентификатор таблицы 
 * @property int $injunction_violation_id Внешний ключ предписания. Для какого предписания создается корректирующие мероприятия
 * @property int $operation_id Внешний ключ операции. Какие корректирующие операции нужны для этого нарушения.
 * @property string|null $date_time Дата и время выполнения, то есть срок выполнения (до какой даты)
 * @property int $status_id
 * @property int|null $worker_id
 * @property string|null $result_correct_measures Результат корректирующего мероприятия
 * @property int|null $correct_measures_value Объём который необходимо выполнить чтобы завершить корректирующее мероприятие
 * @property int|null $attachment_id Внешинй идентификатор вложения (результата)
 * @property string|null $correct_measures_description
 *
 * @property Attachment $attachment
 * @property InjunctionViolation $injunctionViolation
 * @property Operation $operation
 * @property Status $status
 * @property Worker $worker
 * @property CorrectMeasuresAttachment[] $correctMeasuresAttachments
 * @property OrderItem[] $orderItems
 */
class CorrectMeasures extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'correct_measures';
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
            [['injunction_violation_id', 'operation_id', 'status_id'], 'required'],
            [['injunction_violation_id', 'operation_id', 'status_id', 'worker_id', 'correct_measures_value', 'attachment_id'], 'integer'],
            [['date_time'], 'safe'],
            [['result_correct_measures', 'correct_measures_description'], 'string', 'max' => 255],
            [['attachment_id'], 'exist', 'skipOnError' => true, 'targetClass' => Attachment::className(), 'targetAttribute' => ['attachment_id' => 'id']],
            [['injunction_violation_id'], 'exist', 'skipOnError' => true, 'targetClass' => InjunctionViolation::className(), 'targetAttribute' => ['injunction_violation_id' => 'id']],
            [['operation_id'], 'exist', 'skipOnError' => true, 'targetClass' => Operation::className(), 'targetAttribute' => ['operation_id' => 'id']],
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
            'id' => 'Идентификатор таблицы ',
            'injunction_violation_id' => 'Внешний ключ предписания. Для какого предписания создается корректирующие мероприятия',
            'operation_id' => 'Внешний ключ операции. Какие корректирующие операции нужны для этого нарушения.',
            'date_time' => 'Дата и время выполнения, то есть срок выполнения (до какой даты)',
            'status_id' => 'Status ID',
            'worker_id' => 'Worker ID',
            'result_correct_measures' => 'Результат корректирующего мероприятия',
            'correct_measures_value' => 'Объём который необходимо выполнить чтобы завершить корректирующее мероприятие',
            'attachment_id' => 'Внешинй идентификатор вложения (результата)',
            'correct_measures_description' => 'Correct Measures Description',
        ];
    }

    /**
     * Gets query for [[Attachment]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getAttachment()
    {
        return $this->hasOne(Attachment::className(), ['id' => 'attachment_id']);
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
     * Gets query for [[Operation]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getOperation()
    {
        return $this->hasOne(Operation::className(), ['id' => 'operation_id']);
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

    /**
     * Gets query for [[CorrectMeasuresAttachments]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCorrectMeasuresAttachments()
    {
        return $this->hasMany(CorrectMeasuresAttachment::className(), ['correct_measures_id' => 'id']);
    }

    /**
     * Gets query for [[OrderItems]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getOrderItems()
    {
        return $this->hasMany(OrderItem::className(), ['correct_measures_id' => 'id']);
    }
}
