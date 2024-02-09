<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "check_knowledge_worker".
 *
 * @property int $id идентификатор таблицы связки проверки знаний и работников
 * @property int $check_knowledge_id внешний идентификатор проверки знаний
 * @property int $worker_id внешний идентификатор работника
 * @property int $status_id статус проверки знаний работника
 * @property string $number_certificate
 *
 * @property CheckKnowledge $checkKnowledge
 * @property Status $status
 * @property Worker $worker
 * @property CheckKnowledgeWorkerStatus[] $checkKnowledgeWorkerStatuses
 */
class CheckKnowledgeWorker extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'check_knowledge_worker';
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
            [['check_knowledge_id', 'worker_id', 'status_id'], 'required'],
            [['check_knowledge_id', 'worker_id', 'status_id'], 'integer'],
            [['number_certificate'], 'string', 'max' => 255],
            [['check_knowledge_id'], 'exist', 'skipOnError' => true, 'targetClass' => CheckKnowledge::className(), 'targetAttribute' => ['check_knowledge_id' => 'id']],
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
            'id' => 'идентификатор таблицы связки проверки знаний и работников',
            'check_knowledge_id' => 'внешний идентификатор проверки знаний',
            'worker_id' => 'внешний идентификатор работника',
            'status_id' => 'статус проверки знаний работника',
            'number_certificate' => 'Number Certificate',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCheckKnowledge()
    {
        return $this->hasOne(CheckKnowledge::className(), ['id' => 'check_knowledge_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStatus()
    {
        return $this->hasOne(Status::className(), ['id' => 'status_id']);
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
    public function getCheckKnowledgeWorkerStatuses()
    {
        return $this->hasMany(CheckKnowledgeWorkerStatus::className(), ['check_knowledge_worker_id' => 'id']);
    }
}
