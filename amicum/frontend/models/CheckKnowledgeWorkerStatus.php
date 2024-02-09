<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "check_knowledge_worker_status".
 *
 * @property int $id идентификатор таблицы история статусов проверк знаний у работника
 * @property int $check_knowledge_worker_id внешний идентификатор проверки знаний
 * @property int $status_id внешний идентификатор статуса
 * @property string $date_time дата и время смены статуса
 *
 * @property CheckKnowledgeWorker $checkKnowledgeWorker
 * @property Status $status
 */
class CheckKnowledgeWorkerStatus extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'check_knowledge_worker_status';
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
            [['id', 'check_knowledge_worker_id', 'status_id', 'date_time'], 'required'],
            [['id', 'check_knowledge_worker_id', 'status_id'], 'integer'],
            [['date_time'], 'safe'],
            [['id'], 'unique'],
            [['check_knowledge_worker_id'], 'exist', 'skipOnError' => true, 'targetClass' => CheckKnowledgeWorker::className(), 'targetAttribute' => ['check_knowledge_worker_id' => 'id']],
            [['status_id'], 'exist', 'skipOnError' => true, 'targetClass' => Status::className(), 'targetAttribute' => ['status_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'идентификатор таблицы история статусов проверк знаний у работника',
            'check_knowledge_worker_id' => 'внешний идентификатор проверки знаний',
            'status_id' => 'внешний идентификатор статуса',
            'date_time' => 'дата и время смены статуса',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCheckKnowledgeWorker()
    {
        return $this->hasOne(CheckKnowledgeWorker::className(), ['id' => 'check_knowledge_worker_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStatus()
    {
        return $this->hasOne(Status::className(), ['id' => 'status_id']);
    }
}
